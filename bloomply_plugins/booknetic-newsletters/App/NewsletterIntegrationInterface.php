<?php
namespace BookneticAddon\Newsletters;
 
interface NewsletterIntegrationInterface
{
    public function configure(array $settings): void;
    public function subscribe(string $email, string $name = '', string $domain = ''): bool;
} 