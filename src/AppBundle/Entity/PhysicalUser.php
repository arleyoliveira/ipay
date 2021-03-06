<?php

namespace AppBundle\Entity;

use AppBundle\Constants\UserTypes;
use AppBundle\Entity\Traits\PhysicalPersonTrait;
use AppBundle\Form\Type\PhysicalUserType;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Table(name="physical_users")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PhysicalUserRepository")
 */
class PhysicalUser extends PersonUser
{
    use PhysicalPersonTrait;

    /**
     * @return string
     */
    public function getFormTypeClass(): string
    {
        return PhysicalUserType::class;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return UserTypes::PHYSICAL_USER;
    }
}