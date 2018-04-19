<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WorkflowBundle\Component;

use Doctrine\ORM\Mapping\ClassMetadata;
use Mapbender\CoreBundle\Component\EntityHandler;
use Mapbender\CoreBundle\Component\XmlValidator;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
//use Mapbender\CoreBundle\Utils\Object2Array;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WorkflowBundle\Entity\TaskReport;
use OwsProxy3\CoreBundle\Component\ProxyQuery;
use OwsProxy3\CoreBundle\Component\CommonProxy;

/**
 * Description of WmsPingEntityHandler
 *
 * @author Paul Schmidt
 */
class SourcePing extends TaskEntityHandler
{

    /**
     * @inheritdoc
     */
    public function getDefaults()
    {
        return array(
            'rotatingReport' => false,
//            'sources' => array()
        );
    }

    /**
     * Returns class tags as key list.
     */
    public static function getClassTags()
    {
        return array(
            self::generateClassKey("tag.ping"),
            self::generateClassKey("tag.source"),
            self::generateClassKey("tag.workflow"),
            self::generateClassKey("tag.task")
        );
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $sources = $this->getContainer()->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\Source')->findAll();
        foreach ($sources as $source) {
            $report    = null;
            $singleLog = true;
            if ($singleLog) {
                $query     = $this->container->get('doctrine')->getManager()
                    ->createQuery(
                        "SELECT taskreport FROM MapbenderWorkflowBundle:TaskReport taskreport"
                        . " WHERE taskreport.task=:taskid AND taskreport.ident=:ident"
                    )
                    ->setParameter('taskid', $this->entity)
                    ->setParameter('ident', strval($source->getId()));
                $reportTmp = $query->getResult();
                if ($reportTmp && count($reportTmp) === 1) {
                    $report = $reportTmp[0];
                }
            }
            if (!$report) {
                $report = new TaskReport();
                $report->setTask($this->entity)
                    ->setIdent(strval($source->getId()));
            }
            if ($source->getType() === Source::TYPE_WMS) {
                $this->pingWmsSource($report, $source);
            }
        }
    }

    public function pingWmsSource(TaskReport $report, Source $source)
    {
        $start         = round(microtime(true) * 1000);
        $gcurl         = $source->getOriginUrl();
        $url           = UrlUtil::validateUrl($gcurl, array("service" => "WMS", "request" => "GetCapabilities"));
        $report->setAction($url)
            ->setStarttime(new \DateTime());
        $proxy_config  = $this->container->getParameter("owsproxy.proxy");
        $proxy_query   =
            ProxyQuery::createFromUrl($url, $source->getUsername(), $source->getPassword(), array(), array());
        $proxy         = new CommonProxy($proxy_config, $proxy_query);
        $reportHandler = self::createHandler($this->container, $report);
        try {
            $response = $proxy->handle();
            if (200 === $response->getStatusCode()) {
                try {
                    if ($doc = WmsCapabilitiesParser::createDocument($response->getContent())) {
                        $sourceNew = WmsCapabilitiesParser::getParser($doc)->parse();
                        $different = $this->compareWms($source, $sourceNew);
                        if (count($different) === 0) {
                            $source->setStatus(Source::STATUS_OK);
                            $reportHandler->setResult(Source::STATUS_OK, '', true);
                        } else {
                            $source->setStatus(Source::STATUS_TOUPDATE);
                            $reportHandler->setResult(Source::STATUS_TOUPDATE, implode(',', $different), true);
                        }
                    } else {
                        $reportHandler->setResult(Source::STATUS_UNREACHABLE, "No GetCapabilities found.", false);
                    }
                    $reportHandler->setLatency(round(microtime(true) * 1000) - $start);
                } catch (\Exception $e) {
                    $reportHandler->setLatency(round(microtime(true) * 1000) - $start);
                    $reportHandler->setResult(Source::STATUS_UNREACHABLE, $e->getMessage(), false);
                }
            } else {
                $reportHandler->setLatency(round(microtime(true) * 1000) - $start);
                $reportHandler
                    ->setResult(Source::STATUS_UNREACHABLE, "HTTP STATUSCODE: " . $response->getStatusCode(), false);
            }
        } catch (\Exception $e) {
            $reportHandler->setLatency(round(microtime(true) * 1000) - $start);
            $source->setStatus(Source::STATUS_UNREACHABLE);
            $reportHandler->setResult(Source::STATUS_UNREACHABLE, $e->getMessage(), false);
        }
        EntityHandler::createHandler($this->container, $source)->save();
        $reportHandler->save();
    }

    private function compareWms($source1, $source2)
    {
        $em = $this->container->get('doctrine')->getManager();
        $different = $this->compareWmsSources(
            $source1,
            $source2,
            $em->getClassMetadata(EntityUtil::getRealClass($source1)),
            array('uuid','id','alias','status', 'originUrl', 'valid')
        );
        return array_merge(
            $different,
            $this->compareWmsLayers(
                $source1->getRootlayer(),
                $source2->getRootlayer(),
                $em->getClassMetadata(EntityUtil::getRealClass($source1->getRootlayer())),
                array('id', 'cascaded', 'styles', 'priority')
            )
        );
    }

    private function compareWmsSources($object1, $object2, ClassMetadata $classMeta, $ignore = array())
    {
        $different = array();
        $em = $this->container->get('doctrine')->getManager();
        if ($em->contains($object1)) {
            $em->refresh($object1);
        }
        if ($em->contains($object2)) {
            $em->refresh($object2);
        }
        foreach ($classMeta->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $classMeta->getIdentifier()) && !in_array($fieldName, $ignore)
                && ($getMethod = EntityUtil::getReturnMethod($fieldName, $classMeta->getReflectionClass()))) {
                if ($getMethod->invoke($object1) != $getMethod->invoke($object2)) {
//                    $o1 = $getMethod->invoke($object1);
//                    $o2 = $getMethod->invoke($object2);
                    $dieferent[] = 'sourcefield:' . $fieldName;
                }
            }
        }

        return $different;
    }

    private function compareWmsLayers($layer1, $layer2, ClassMetadata $classMeta, $ignore = array())
    {
        $dieferent = array();
        $em = $this->container->get('doctrine')->getManager();
        if ($em->contains($layer1)) {
            $em->refresh($layer1);
        }
        if ($em->contains($layer2)) {
            $em->refresh($layer2);
        }
        foreach ($classMeta->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $classMeta->getIdentifier()) && !in_array($fieldName, $ignore)
                && ($getMethod = EntityUtil::getReturnMethod($fieldName, $classMeta->getReflectionClass()))) {
                if ($getMethod->invoke($layer1) != $getMethod->invoke($layer2)) {
//                    $o1 = $getMethod->invoke($layer1);
//                    $o2 = $getMethod->invoke($layer2);
                    $dieferent[] = 'layerfield:' . $fieldName;
                }
            }
        }
        if ($layer1->getSublayer()->count() !== $layer2->getSublayer()->count()) {
            $dieferent[] = 'layercount:' . $layer1->getSublayer()->count() . "," . $layer2->getSublayer()->count();
            return $dieferent;
        }
        for ($i = 0, $count = $layer1->getSublayer()->count(); $i < $count; $i++) {
            $dieferent = array_merge(
                $dieferent,
                $this->compareWmsLayers(
                    $layer1->getSublayer()->get($i),
                    $layer2->getSublayer()->get($i),
                    $classMeta,
                    $ignore
                )
            );
        }
        return $dieferent;
    }

    /**
     * @inheritdoc
     */
    public function renderReport()
    {
        return $this->container->get('templating')
            ->render(
                'MapbenderWorkflowBundle:Task:sourceping-report.html.twig',
                array('task' => $this->getEntity())
            );
    }

    /**
     * @inheritdoc
     */
    public function targetFromReport(TaskReport $report)
    {
        $source = $this->getContainer()->get("doctrine")
                ->getRepository('Mapbender\CoreBundle\Entity\Source')->find($report->getIdent());
        return $source;
    }
}
