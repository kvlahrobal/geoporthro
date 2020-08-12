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
class ThematicSearchOneAdminType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return 'thematicsearchone';
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
                    "anundverkauf" => "An- und Verkauf",
                    "anlagevermoegendereigenbetriebe" => "Anlagevermögen der Eigenbetriebe",
                    "baumkataster" => "Baumkataster",
                    "bebauungsplaene" => "Bebauungspläne",
                    "bebauungsplaene_nur_rk" => "Bebauungspläne",
                    "betriebegewerblicherart" => "Betriebe gewerblicher Art",
                    "grundvermoegen" => "Grundvermögen",
                    "gruenfriedhofsflaechen" => "Grünflächen und Friedhofsbegleitflächen",
                    "gruenpflegeobjekte" => "Grünpflegeobjekte",
                    "ingenieurbauwerke" => "Ingenieurbauwerke",
                    "kleingartenanlagen" => "Kleingartenanlagen",
                    "leuchten" => "Leuchten",
                    "leuchtenschalteinrichtungen" => "Leuchtenschalteinrichtungen",
                    "lichtsignalanlagen" => "Lichtsignalanlagen",
                    "mietenpachten" => "Mieten und Pachten",
                    "spielgeraete" => "Spielgeräte",
                    "strassennetz" => "Straßennetz",
                    "wirtschaftseinheiten_wiro" => "WIRO-Wirtschaftseinheiten"))
            )
            ->add('target', 'target_element',
                array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false));
    }
}

?>
