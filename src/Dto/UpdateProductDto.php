<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateProductDto
{
    #[Assert\Length(
        max: 255,
        maxMessage: 'Name cannot be longer than {{ limit }} characters.'
    )]
    public ?string $name = null;

    #[Assert\Positive(message: 'Price must be greater than 0.')]
    public ?float $price = null;
}
