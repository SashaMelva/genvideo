<?php
namespace App\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;

abstract class UserController extends RestController
{
    protected Mailer $mailer;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $smtp_config = $container->get('smtp_config');
        $transport = Transport::fromDsn('smtp://'.$smtp_config['smtp_username'].':'.$smtp_config['smtp_password'].'@'.$smtp_config['smtp_server'].':'.$smtp_config['smtp_port']);
        $this->mailer = new Mailer($transport);
    }

    public function getOrigin()
    {
        $school_header = $this->request->getHeaderLine('Origin');
        preg_match('/\/\/(.*?)\./', $school_header, $output_array);
        return $output_array[1];
    }
}