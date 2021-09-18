<?php

namespace AppBundle\Service;

use AppBundle\Constants\UserTypes;
use AppBundle\Entity\Factories\UserFactory;
use AppBundle\Entity\Interfaces\IEntity;
use AppBundle\Entity\LegalUser;
use AppBundle\Entity\PersonUser;
use AppBundle\Entity\PhysicalUser;
use AppBundle\Entity\User;
use AppBundle\Exceptions\AbstractException;
use AppBundle\Exceptions\Factories\ExceptionFactory;
use AppBundle\Exceptions\Http\BadRequestHttpException;
use AppBundle\Exceptions\InvalidUserException;
use AppBundle\Form\Serializes\FormErrorSerializer;
use AppBundle\Middleware\CheckExistPhysicalUserByCpf;
use AppBundle\Middleware\CheckExistUserByEmail;
use AppBundle\Repository\PersonUserRepository;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserService extends AbstractService
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function attachFormFactory(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param EncoderFactoryInterface $encoderFactory
     */
    public function attachEncoderFactoryInterface(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * @param array $data
     * @return string
     * @throws AbstractException
     */
    public function getUserTypeByData(array $data): string
    {
        if (isset($data['cnpj'])) {
            return UserTypes::LEGAL_USER;
        } else if (isset($data['cpf'])) {
            return UserTypes::PHISICAL_USER;
        }

        throw ExceptionFactory::create(
            InvalidUserException::class,
            "As informações do usuário são inválidas!"
        );
    }

    /**
     * @param User $user
     * @throws AbstractException
     */
    private function checkExistUser(User $user)
    {
        if ($this->repository instanceof PersonUserRepository) {
            $middleware = new CheckExistUserByEmail(
                'email',
                $user->getEmail(),
                $user->getId(),
                $this->repository
            );

            $current = $middleware;

            if ($user instanceof PhysicalUser) {
                $current = $current->linkWith(new CheckExistPhysicalUserByCpf(
                    'cpf',
                    $user->getCpf(),
                    $user->getId(),
                    $this->em->getRepository(PhysicalUser::class)
                ));
            } else if ($user instanceof LegalUser) {
                $current = $current->linkWith(new CheckExistPhysicalUserByCpf(
                    'cnpj',
                    $user->getCnpj(),
                    $user->getId(),
                    $this->em->getRepository(LegalUser::class)
                ));
            }
            
            $middleware->check();
        }
    }

    /**
     * @param User $user
     */
    public function encoderPassword(User $user)
    {
        $encoder = $this->encoderFactory->getEncoder($user);
        $password = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($password);
    }

    /**
     * @param User $user
     * @param $data
     * @throws AbstractException
     * @throws OptimisticLockException
     */
    public function processSaveForm(User $user, $data): void
    {
        $form = $this->formFactory->create($user->getFormTypeClass(), $user, [
            'csrf_protection' => false
        ]);

        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->checkExistUser($user);

            if (null !== $user->getPlainPassword()) {
                $this->encoderPassword($user);
            }

            $this->persist($user);
        } else {
            throw ExceptionFactory::create(
                BadRequestHttpException::class,
                "Erro na validação dos dados!",
                FormErrorSerializer::serialize($form)
            );
        }
    }

    /**
     * @param array $data
     * @return IEntity
     * @throws AbstractException
     * @throws OptimisticLockException
     */
    public function create(array $data): IEntity
    {
        $userType = $this->getUserTypeByData($data);
        $user = UserFactory::create($userType);

        $this->processSaveForm($user, $data);

        return $user;
    }

    /**
     * @param IEntity $entity
     * @param array $data
     * @return IEntity
     * @throws AbstractException
     * @throws OptimisticLockException
     */
    public function update(IEntity $entity, array $data): IEntity
    {
        $this->processSaveForm($entity, $data);

        return $entity;
    }
}