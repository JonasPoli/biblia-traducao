<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * "Meu Perfil": o que o próprio usuário pode alterar.
 * E-mail, usuário e grupos NÃO fazem parte do formulário (são só exibidos).
 * A senha só muda se "Nova senha" for preenchida; aí a senha atual é obrigatória.
 */
class ProfileType extends AbstractType
{
    public const STATES = [
        'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA',
        'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
    ];

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nome completo',
                'constraints' => [
                    new Assert\NotBlank(message: 'Informe o seu nome.'),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Foto',
                'mapped' => false,
                'required' => false,
                'help' => 'JPG, PNG ou WEBP, até 5 MB.',
                'attr' => ['accept' => 'image/jpeg,image/png,image/webp'],
                'constraints' => [
                    new Assert\Image(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Envie uma imagem JPG, PNG ou WEBP.',
                        maxSizeMessage: 'A imagem deve ter no máximo 5 MB.',
                    ),
                ],
            ])
            ->add('removeImage', CheckboxType::class, [
                'label' => 'Remover foto atual',
                'mapped' => false,
                'required' => false,
            ])
            ->add('phone', TelType::class, [
                'label' => 'Telefone',
                'required' => false,
                'attr' => ['placeholder' => '(16) 99999-9999', 'autocomplete' => 'tel'],
                'constraints' => [
                    new Assert\Length(max: 30),
                    new Assert\Regex(pattern: '/^[0-9()+\-.\s]{8,30}$/', message: 'Telefone inválido. Use apenas números, espaços, parênteses, + e -.'),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Endereço',
                'required' => false,
                'attr' => ['placeholder' => 'Rua, número, complemento, bairro', 'autocomplete' => 'street-address'],
                'constraints' => [new Assert\Length(max: 255)],
            ])
            ->add('city', TextType::class, [
                'label' => 'Cidade',
                'required' => false,
                'attr' => ['autocomplete' => 'address-level2'],
                'constraints' => [new Assert\Length(max: 120)],
            ])
            ->add('state', ChoiceType::class, [
                'label' => 'Estado',
                'required' => false,
                'placeholder' => 'Selecione',
                'choices' => array_combine(self::STATES, self::STATES),
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Senha atual',
                'mapped' => false,
                'required' => false,
                'always_empty' => true,
                'attr' => ['autocomplete' => 'current-password'],
                'help' => 'Obrigatória somente se for trocar a senha.',
            ])
            ->add('newPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => false,
                'invalid_message' => 'A confirmação não confere com a nova senha.',
                'first_options' => [
                    'label' => 'Nova senha',
                    'help' => 'Deixe em branco para manter a senha atual.',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'second_options' => [
                    'label' => 'Confirme a nova senha',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'constraints' => [
                    new Assert\Length(min: 6, max: 4096, minMessage: 'A nova senha deve ter pelo menos {{ limit }} caracteres.'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'constraints' => [
                new Assert\Callback(function (User $user, ExecutionContextInterface $context): void {
                    /** @var FormInterface $form */
                    $form = $context->getRoot();
                    $newPassword = (string) $form->get('newPassword')->getData();

                    // Sem nova senha → o usuário não quer trocar; ignora a senha atual.
                    if ($newPassword === '') {
                        return;
                    }

                    $current = (string) $form->get('currentPassword')->getData();
                    if ($current === '' || !$this->passwordHasher->isPasswordValid($user, $current)) {
                        $context->buildViolation('Senha atual incorreta.')
                            ->atPath('children[currentPassword]')
                            ->addViolation();
                    }
                }),
            ],
        ]);
    }
}
