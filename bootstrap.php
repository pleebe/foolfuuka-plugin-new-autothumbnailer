<?php

use Doctrine\DBAL\Schema\Schema;
use Foolz\FoolFrame\Model\Autoloader;
use Foolz\FoolFrame\Model\Context;
use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\FoolFrame\Model\Plugins;
use Foolz\FoolFrame\Model\Uri;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\Plugin\Event;
use Symfony\Component\Routing\Route;

class HHVM_AT
{
    public function run()
    {
        Event::forge('Foolz\Plugin\Plugin::execute#foolz/foolfuuka-plugin-new-autothumbnailer')
            ->setCall(function ($result) {
                /* @var Context $context */
                $context = $result->getParam('context');
                /** @var Autoloader $autoloader */
                $autoloader = $context->getService('autoloader');

                $autoloader->addClassMap([
                    'Foolz\FoolFrame\Controller\Admin\Plugins\AutoThumbnailer' => __DIR__ . '/classes/controller/admin.php',
                    'Foolz\FoolFuuka\Plugins\AutoThumbnailer\Console\Console' => __DIR__ . '/classes/console/console.php'
                ]);

                Event::forge('Foolz\FoolFrame\Model\Context::handleWeb#obj.afterAuth')
                    ->setCall(function ($result) use ($context) {
                        // don't add the admin panels if the user is not an admin
                        if ($context->getService('auth')->hasAccess('maccess.admin')) {
                            Event::forge('Foolz\FoolFrame\Controller\Admin::before#var.sidebar')
                                ->setCall(function ($result) {
                                    $sidebar = $result->getParam('sidebar');
                                    $sidebar[]['plugins'] = [
                                        "content" => ["auto_thumbnailer/manage" => ["level" => "admin", "name" => _i("SWF thumbnailer"), "icon" => 'icon-bar-chart']]
                                    ];
                                    $result->setParam('sidebar', $sidebar);
                                });

                            $context->getRouteCollection()->add(
                                'foolframe.plugin.auto_thumbnailer.admin', new Route(
                                    '/admin/plugins/auto_thumbnailer/{_suffix}',
                                    [
                                        '_suffix' => 'manage',
                                        '_controller' => '\Foolz\FoolFrame\Controller\Admin\Plugins\AutoThumbnailer::manage'
                                    ],
                                    [
                                        '_suffix' => '.*'
                                    ]
                                )
                            );
                        }

                    });

                Event::forge('Foolz\FoolFrame\Model\Context::handleConsole#obj.app')
                    ->setCall(function ($result) use ($context) {
                        $result->getParam('application')
                            ->add(new \Foolz\FoolFuuka\Plugins\AutoThumbnailer\Console\Console($context));
                    });

            });
    }
}

(new HHVM_AT())->run();
