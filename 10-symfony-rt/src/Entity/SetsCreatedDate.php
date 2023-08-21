<?php
/**
 * Author: Azamat Holmeer
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

trait SetsCreatedDate
{
    #[ORM\PrePersist]
    public function setCreatedDatePrePersist(): void
    {
        $this->createdDate = $this->createdDate ?? date_create();
    }
}
