<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Interfaces\IEntity;
use AppBundle\Entity\Interfaces\IUser;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="`users`")
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="type", type="string")
 * @ORM\DiscriminatorMap({
 *      "PERSON_USER" = "AppBundle\Entity\PersonUser",
 *      "PHYSICAL_USER" = "AppBundle\Entity\PhysicalUser",
 *      "LEGAL_USER"    = "AppBundle\Entity\LegalUser"
 * })
 * @ORM\AttributeOverrides({
 *  @ORM\AttributeOverride(
 *      name="salt",
 *      column=@ORM\Column(name="salt", type="string", nullable=true)
 *      )
 *  })
 * @ORM\HasLifecycleCallbacks()
 */
abstract class User extends BaseUser implements IUser, IEntity
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct()
    {
        parent::__construct();
        $this
            ->setEnabled(true)
            ->setRoles(['ROLE_USER'])
            ->setSuperAdmin(false)
        ;
    }

    abstract public function getType(): string;

    public function setEmail($email)
    {
        $this->setUsername($email);
        $this->setUsernameCanonical($email);
        $this->setEmailCanonical($email);
        return parent::setEmail($email);
    }
}