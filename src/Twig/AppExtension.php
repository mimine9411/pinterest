<?php

namespace App\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('pluralize', [$this, 'pluralize']),
            new TwigFunction('make_tags_clickable', [$this, 'make_tags_clickable']),
        ];
    }

    public function pluralize(int $count, string $singular, ?string $plural = null): string
    {
        $plural ??= $singular . 's';

        $str = $count === 1 ? $singular : $plural;

        return "$count $str";
    }

    function make_tags_clickable($description){

        $html = preg_replace('/#[^#|\s]+/', '<a href="/pins/tags/$0">$0</a>', $description);
        return preg_replace('/\/#/', '/', $html);
    }
}
