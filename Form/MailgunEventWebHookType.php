<?php

namespace Azine\MailgunWebhooksBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class MailgunEventType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('event')
            ->add('domain')
            ->add('description')
            ->add('notification')
            ->add('reason')
            ->add('recipient')
            ->add('errorCode')
            ->add('ip')
            ->add('error')
            ->add('country')
            ->add('city')
            ->add('region')
            ->add('campaignId')
            ->add('campaignName')
            ->add('clientName')
            ->add('clientOs')
            ->add('clientType')
            ->add('deviceType')
            ->add('mailingList')
            ->add('messageHeaders')
            ->add('messageId')
            ->add('tag')
            ->add('customVariables')
            ->add('userAgent')
            ->add('url')
            ->add('token')
            ->add('timestamp')
            ->add('signature')
        ;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Azine\MailgunWebhooksBundle\Entity\MailgunEvent'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return '';
    }
}
