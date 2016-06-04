<?php

namespace Foolz\FoolFrame\Controller\Admin\Plugins;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Foolz\FoolFrame\Model\Validation\ActiveConstraint\Trim;

class AutoThumbnailer extends \Foolz\FoolFrame\Controller\Admin
{
    public function before()
    {
        parent::before();

        $this->param_manager->setParam('controller_title', _i('Plugins'));
    }

    public function security()
    {
        return $this->getAuth()->hasAccess('maccess.admin');
    }

    protected function structure()
    {
        $arr = [
            'open' => [
                'type' => 'open',
            ],
            'foolfuuka.plugins.auto_thumbnailer.enabled' => [
                'preferences' => true,
                'type' => 'checkbox',
                'label' => '',
                'help' => _i('Enabled automatic thumbnails on /f/? This sets the thumbnailer to generate images from SWF files. <br>Run the daemon from the FoolFuuka directory.').
                '<pre>php console auto_thumbnailer:run</pre>'
            ],
            'foolfuuka.plugins.auto_thumbnailer.gnash' => [
                'preferences' => true,
                'type' => 'input',
                'label' => _i('Gnash binary location'),
                'help' => _i('Full path to dump-gnash'),
                'class' => 'span3',
                'validation' => [new Trim()]
            ],
            'foolfuuka.plugins.auto_thumbnailer.gnashrc' => [
                'preferences' => true,
                'type' => 'input',
                'label' => _i('Gnashrc file location'),
                'help' => _i('This is useful for setting security limits to Gnash. Example file in plugin directory "private".'),
                'class' => 'span3',
                'validation' => [new Trim()]
            ],
            'foolfuuka.plugins.auto_thumbnailer.chown' => [
                'preferences' => true,
                'type' => 'input',
                'label' => _i('Chown the resulting thumbnails'),
                'help' => _i('Set this if you want to chown generated thumbnails. Syntax: user:group'),
                'class' => 'span3',
                'validation' => [new Trim()]
            ],
            'separator' => [
                'type' => 'separator-short',
            ],
            'submit' => [
                'type' => 'submit',
                'class' => 'btn-primary',
                'value' => _i('Submit')
            ],
            'close' => [
                'type' => 'close'
            ],
        ];

        return $arr;
    }

    public function action_manage()
    {
        $this->param_manager->setParam('method_title', [_i('FoolFuuka'), _i("Auto Thumbnailer"),_i('Manage')]);

        $data['form'] = $this->structure();

        $this->preferences->submit_auto($this->getRequest(), $data['form'], $this->getPost());

        // create a form
        $this->builder->createPartial('body', 'form_creator')
            ->getParamManager()->setParams($data);

        return new Response($this->builder->build());
    }
}
