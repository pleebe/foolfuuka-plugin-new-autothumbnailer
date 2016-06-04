<?php

namespace Foolz\FoolFuuka\Plugins\AutoThumbnailer\Console;

use Foolz\FoolFrame\Model\Context;
use Foolz\FoolFrame\Model\DoctrineConnection;
use Foolz\FoolFuuka\Model\RadixCollection;
use Foolz\FoolFrame\Model\Preferences;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends Command
{
    /**
     * @var \Foolz\FoolFrame\Model\Context
     */
    protected $context;

    /**
     * @var DoctrineConnection
     */
    protected $dc;

    /**
     * @var RadixCollection
     */
    protected $radix_coll;

    /**
     * @var Preferences
     */
    protected $preferences;

    public function __construct(Context $context)
    {
        $this->context = $context;
        $this->dc = $context->getService('doctrine');
        $this->radix_coll = $context->getService('foolfuuka.radix_collection');
        $this->preferences = $context->getService('preferences');
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('auto_thumbnailer:run')
            ->setDescription('Runs the SWF thumbnailer in an endless loop')
            ->addOption(
                'radix',
                null,
                InputOption::VALUE_OPTIONAL,
                _i('Defaults to /f/')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (($radix = $input->getOption('radix')) !== null) {
            if ($this->radix_coll->getByShortname($radix) !== false) {
                $this->auto_thumbnailer($output, $radix);
            } else {
                $output->writeln('<error>'._i('Wrong radix (board short name) specified.').'</error>');
            }
        } else {
            $this->auto_thumbnailer($output);
        }
    }

    public function auto_thumbnailer($output, $shortname = 'f')
    {
        if(!$this->preferences->get('foolfuuka.plugins.auto_thumbnailer.enabled')) {
            return;
        }
        while(true) {
            $board = $this->radix_coll->getByShortname($shortname);
            $runmode = date("H");
            if($runmode=='1')
            {
                //daily
                $medias = $this->dc->qb()
                    ->select('*')
                    ->from($board->getTable( '_images'), 'bi')
                    ->where('banned = 0')
                    ->orderBy('media_id', 'desc')
                    ->execute()
                    ->fetchAll();
                $mode = 'daily';
            } else {
                //hourly
                $medias = $this->dc->qb()
                    ->select('*')
                    ->from($board->getTable( '_images'), 'bi')
                    ->where('banned = 0')
                    ->setMaxResults(100)
                    ->orderBy('media_id', 'desc')
                    ->execute()
                    ->fetchAll();
                $mode = 'hourly';
            }
            $output->writeln("[$shortname, $mode] * Running");

            if($this->preferences->get('foolfuuka.plugins.auto_thumbnailer.gnash')) {
                $bin = $this->preferences->get('foolfuuka.plugins.auto_thumbnailer.gnash');
            } else {
                $bin = 'dump-gnash';
            }

            if($this->preferences->get('foolfuuka.plugins.auto_thumbnailer.gnashrc')) {
                $export = 'GNASHRC='.$this->preferences->get('foolfuuka.plugins.auto_thumbnailer.gnashrc');
            } else {
                $export = '';
            }

            $chown = null;

            if($this->preferences->get('foolfuuka.plugins.auto_thumbnailer.chown')) {
                $chown = $this->preferences->get('foolfuuka.plugins.auto_thumbnailer.chown');
            }

            foreach($medias as $media) {
                $media_id = $media["media_id"];
                if(isset($media["media"])&&$media["media"]!=='') {
                    $file = $media["media"];
                    //Let's try to find the media
                    $swf = $this->preferences->get('foolfuuka.boards.directory').'/'.$shortname.'/image/'.substr($file, 0, 4).'/'.substr($file, 4, 2).'/'.$file;
                    if (!file_exists($swf)) {
                        $output->writeln("[$shortname, $mode] media file of $media_id doesn't exist. Skipping...");
                    }
                    else
                    {
                        if(isset($media["preview_op"])&&$media["preview_op"]!=='') {
                            $preview_op = $media["preview_op"];
                            //Let's try to find the preview op
                            $prew_op = $this->preferences->get('foolfuuka.boards.directory').'/'.$shortname.'/thumb/'.substr($preview_op, 0, 4).'/'.substr($preview_op, 4, 2).'/'.$preview_op;
                            if (!file_exists($prew_op)) {
                                $output->writeln("[$shortname, $mode] preview op file of $media_id doesn't exist. Generating...");
                                exec('mkdir -p "'.$this->preferences->get('foolfuuka.boards.directory').'/'.$shortname.'/thumb/'.substr($preview_op, 0, 4).'/'.substr($preview_op, 4, 2).'/"');
                                if(!file_exists($this->preferences->get('foolframe.imagick.convert_path'))) {
                                    exec("$export $bin --screenshot last --screenshot-file \"$prew_op\" \"$swf\" --max-advances=100 --timeout=100 --width=250 --height=250 -r1");
                                } else {
                                    exec("$export $bin --screenshot last --screenshot-file \"$prew_op\" \"$swf\" --max-advances=100 --timeout=100 -r1");
                                    exec($this->preferences->get('foolframe.imagick.convert_path') . " -thumbnail 250 \"$prew_op\" \"$prew_op\"");
                                }
                                if($chown) {
                                    exec("chown $chown \"$prew_op\"");
                                }
                            }
                        }
                        if(isset($media["preview_reply"])&&$media["preview_reply"]!=='') {
                            $preview_reply = $media["preview_reply"];
                            //Let's try to find the preview reply
                            $prew_reply = $this->preferences->get('foolfuuka.boards.directory').'/'.$shortname.'/thumb/'.substr($preview_reply, 0, 4).'/'.substr($preview_reply, 4, 2).'/'.$preview_reply;
                            if (!file_exists($prew_reply)) {
                                $output->writeln("[$shortname, $mode] preview reply file of $media_id doesn't exist. Generating...");
                                exec('mkdir -p "'.$this->preferences->get('foolfuuka.boards.directory').'/'.$shortname.'/thumb/'.substr($preview_reply, 0, 4).'/'.substr($preview_reply, 4, 2).'/"');
                                if(!file_exists($this->preferences->get('foolframe.imagick.convert_path'))) {
                                    exec("$export $bin --screenshot last --screenshot-file \"$prew_reply\" \"$swf\" --max-advances=100 --timeout=100 --width=125 --height=125 -r1");
                                } else {
                                    exec("$export $bin --screenshot last --screenshot-file \"$prew_reply\" \"$swf\" --max-advances=100 --timeout=100 -r1");
                                    exec($this->preferences->get('foolframe.imagick.convert_path') . " -thumbnail 125 \"$prew_reply\" \"$prew_reply\"");
                                }
                                if($chown) {
                                    exec("chown $chown \"$prew_reply\"");
                                }
                            }
                        }
                    }
                }
            }
            $output->writeln("[$shortname, $mode] * Sleeping");

            sleep(3600);
        }
    }
}
