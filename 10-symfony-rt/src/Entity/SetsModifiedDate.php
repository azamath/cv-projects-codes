<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait SetsModifiedDate
{
    #[ORM\PrePersist]
    public function setModifiedDatePrePersist(): void
    {
        $this->modifiedDate = $this->modifiedDate ?? date_create();
    }

    #[ORM\PreUpdate]
    public function setModifiedDatePreUpdate(): void
    {
        $this->modifiedDate = date_create();
    }
}
