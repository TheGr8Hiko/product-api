<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateProductDto
{
    #[Assert\NotBlank(message: 'Name is required.')]
    #[Assert\Length(
        max: 255,
        maxMessage: 'Name cannot be longer than {{ limit }} characters.'
    )]
    public ?string $name = null;

    #[Assert\NotNull(message: 'Price is required.')]
    #[Assert\Positive(message: 'Price must be greater than 0.')]
    public ?float $price = null;
}
