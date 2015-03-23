<?php

namespace Netglue;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Uri\UriFactory;
use Zend\Uri\Exception as UriException;
use Zend\Http\Client as HttpClient;
use Zend\Http\Exception as HttpException;
use DOMDocument;
use DOMXPath;

class CacheWarmCommand extends Command
{

    private $client;
    private $output;
    private $input;

    protected function configure()
    {
        $this->setName('warm')
             ->setDescription('Warm up a cache by crawling a sitemap file')
             ->addArgument('sitemap', InputArgument::REQUIRED, 'Full XML SiteMap URI')
             ->addOption('unsafe', null, InputOption::VALUE_NONE, 'Turns off SSL certificate verification');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $sitemap = $input->getArgument('sitemap');

        $body = $this->loadSitemapBody($sitemap, $output);
        if(false === $body) {
            return 1;
        }

        $doc = new DOMDocument();
        if(false === $doc->loadXml($body)) {
            $output->writeln('<error>Failed to parse XML into a DOMDocument</error>');
            return 1;
        }

        $urls = $this->extractSitemapUrls($doc);

        if ($this->output->isVerbose()) {
            $this->output->writeln(sprintf('<info>Found %d URIs to warm up</info>', count($urls)));
        }

        foreach($urls as $uri) {
            if ($output->isVeryVerbose()) {
                $this->output->writeln(sprintf('<info>Warming %s</info>', $uri));
            }
            $client = $this->getHttpClient();
            $client->setUri($uri);
            $client->send();
        }

    }

    /**
     * Return an array of URLs to warm up from a single sitemap or sitemap index file
     * @param DOMDocument $doc
     * @return array
     */
    private function extractSitemapUrls(DOMDocument $doc)
    {
        if($this->isIndex($doc)) {
            $sitemaps = $this->getSitemaps($doc);

            if ($this->output->isVerbose()) {
                $this->output->writeln(sprintf('<info>Found sitemap index with %d child sitemaps</info>', count($sitemaps)));
            }

            $urls = [];

            foreach($sitemaps as $url) {
                $body = $this->loadSitemapBody($url);
                $child = new DOMDocument();
                $child->loadXml($body);
                $urls = array_merge($urls, $this->extractSitemapUrls($child));
            }

            return $urls;
        }
        $urls = [];
        $nodeList = $doc->getElementsByTagName('loc');
        foreach($nodeList as $url) {
            $urls[] = $url->nodeValue;
        }
        return $urls;
    }

    /**
     * Whether the given document loks like a sitemap index file
     * @param DOMDocument $doc
     * @return bool
     */
    private function isIndex(DOMDocument $doc)
    {
        $nodeList = $doc->getElementsByTagName('sitemapindex');
        if($nodeList->length === 0) {
            return false;
        }
        return true;
    }

    /**
     * Return Sitemap URLs from a Sitemap Index File
     * @param DOMDocument $doc
     * @return array
     */
    private function getSitemaps(DOMDocument $doc)
    {
        $sitemaps = [];
        $nodeList = $doc->getElementsByTagName('loc');
        foreach($nodeList as $sitemap) {
            $sitemaps[] = $sitemap->nodeValue;
        }
        return $sitemaps;
    }

    /**
     * Load remote sitemap xml into a string
     * @param string $sitemapUri Url of sitemap
     * @param OutputInterface $output Output to append errors to if any
     * @return string|false
     */
    private function loadSitemapBody($sitemapUri)
    {
        try {
            $uri = UriFactory::factory($sitemapUri);
        } catch(UriException\ExceptionInterface $e) {
            $this->output->writeln(sprintf('<error>Invalid Sitemap URI: %s</error>', $e->getMessage()));
            return false;
        }

        try {
            $client = $this->getHttpClient()->setUri($uri);
            $response = $client->send();
        } catch(HttpException\ExceptionInterface $e) {
            $this->output->writeln(sprintf('<error>Failed to load Sitemap: %s</error>', $e->getMessage()));
            return false;
        }

        return $response->getBody();
    }

    /**
     * Return reusable http client
     * @return HttpClient
     */
    private function getHttpClient()
    {
        if(!$this->client) {
            $options = ['adapter' => 'Zend\Http\Client\Adapter\Curl'];
            $this->client = new HttpClient(null, $options);
            if($this->input->getOption('unsafe')) {
                $this->output->writeln('<comment>Disabling SSL Certificate Verification</comment>');
                $adapter = $this->client->getAdapter();
                $adapter->setCurlOption(CURLOPT_SSL_VERIFYHOST, false);
                $adapter->setCurlOption(CURLOPT_SSL_VERIFYPEER, false);
            }

        }
        return $this->client;
    }

}
