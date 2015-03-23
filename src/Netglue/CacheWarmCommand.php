<?php

namespace Netglue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CacheWarmCommand extends Command
{

    protected function configure()
    {
        $this->setName('cache-warm')
             ->setDescription('Warm up a cache by crawling a sitemap file')
             ->addArgument('sitemap', InputArgument::REQUIRED, 'Full XML SiteMap URI');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sitemap = $input->getArgument('sitemap');
        $output->writeln($sitemap);
    }

}
