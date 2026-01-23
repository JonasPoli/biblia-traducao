<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
            ])
            ->add('username', TextType::class, [
                'label' => 'Usuário',
                'help' => 'Usado para login se diferente do e-mail (opcional)',
            ])
            ->add('workGroup', ChoiceType::class, [
                'label' => 'Grupo de Trabalho',
                'choices' => [
                    'Administrador (Grupo 0)' => 0,
                    'Tradutor (Grupo 1)' => 1,
                    'Revisor de Tradução (Grupo 2)' => 2,
                    'Autor de Paratextos (Grupo 3)' => 3,
                    'Revisor de Paratextos (Grupo 4)' => 4,
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'label' => 'Senha',
                'mapped' => false,
                'required' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new Length([
                        'min' => 6,
                        'minMessage' => 'Your password should be at least {{ limit }} characters',
                        // max length allowed by Symfony for security reasons
                        'max' => 4096,
                    ]),
                ],
                'help' => 'Deixe em branco para manter a senha atual (na edição)',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
