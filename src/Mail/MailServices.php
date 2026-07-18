<?php

declare(strict_types=1);

namespace Atom\Mail;

use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Di\ServiceProviderInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

final readonly class MailServices implements ServiceProviderInterface
{
    public function register(Bindings $bindings): void
    {
        $bindings->bind(MailTemplateRenderer::class)
            ->toSelf()
            ->singleton();

        $bindings->bind(MailerInterface::class)
            ->toFactory(static function (Injector $injector): SymfonyMailer {
                $options = $injector->get(MailOptions::class);

                return new SymfonyMailer(
                    new Mailer(Transport::fromDsn($options->dsn)),
                    $injector->get(MailTemplateRenderer::class),
                    $options
                );
            })
            ->singleton();
    }
}
