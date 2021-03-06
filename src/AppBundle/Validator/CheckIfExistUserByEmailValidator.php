<?php

namespace AppBundle\Validator;

use AppBundle\Exceptions\AbstractException;
use AppBundle\Exceptions\Factories\ExceptionFactory;
use AppBundle\Exceptions\InvalidUserException;
use AppBundle\Repository\PersonUserRepository;

class CheckIfExistUserByEmailValidator extends CheckIfExistUserValidator
{
    /**
     * @var string
     */
    private $email;

    /**
     * @param string $columnName
     * @param string $email
     * @param int|null $ignoreUserId
     * @param PersonUserRepository $repository
     */
    public function __construct(string $columnName, string $email, ?int $ignoreUserId, PersonUserRepository $repository)
    {
        parent::__construct($columnName, $ignoreUserId, $repository);
        $this->email = $email;
    }

    /**
     * @return bool
     * @throws AbstractException
     */
    public function check(): bool
    {
        $user = $this->repository->findOneBy([$this->columnName => $this->email]);

        if (null !== $user && $user->getId() !== $this->ignoreUserId) {
            throw ExceptionFactory::create(
                InvalidUserException::class,
                "Já existe um usuário com este e-mail ({$this->email}) cadastrado!"
            );
        }

        return $this->checkNext();
    }
}