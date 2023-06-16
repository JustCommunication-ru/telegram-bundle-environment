<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    JustCommunication\TelegramBundle\TelegramBundle::class => ['all' => true],
    JustCommunication\CacheBundle\CacheBundle::class => ['all' => true],
    JustCommunication\FuncBundle\FuncBundle::class => ['all' => true],
    JustCommunication\SmsAeroBundle\SmsAeroBundle::class => ['all' => true],
];
