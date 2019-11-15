<?php

namespace Mapbender\AlkisBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Description of SearchAdminType
 *
 * @author Paul Schmidt
 */
class BaseSearchOneAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'basesearchone';
    }

    /**
     * @inheritdoc
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('tooltip', 'text', array('required' => false))
            ->add('buffer', 'number', array('required' => true))
            ->add('options', 'choice',
                array(
                'required' => true,
                'multiple' => true,
                'choices' => array(
                    "addr" => "Adressen",
                    "auftrag" => "Auftragsverwaltung",
                    "eigen" => "Eigentümer",
                    "flur" => "Flurstücke",
                    "grund" => "Grundbuchblätter",
                    "risse_fst" => "Risse zu Flurstücken",
                    "schiffe" => "Schiffssuche Hanse Sail"))
            )
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('alkisinfo', 'target_element',
                array(
                'element_class' => 'Mapbender\\AlkisBundle\\Element\\AlkisInfo',
                'application' => $options['application'],
                'property_path' => '[alkisinfo]',
                'required' => false)
            );
    }
}

?>
