<?php
namespace Ordman\WsdlDownloader;

use DOMDocument;
use DOMElement;
use DOMNodeList;
use Psr\Log\LoggerInterface;

Class WsdlParser {

  private string $outputDir;
  private LoggerInterface $logger;

  public function __construct(string $dir, LoggerInterface $logger)
  {
    $this->outputDir = $dir;
    $this->logger = $logger;
    $this->makeDir($this->outputDir);
  }

  private function makeDir($name): void
  {
    if (!is_dir($name) && !mkdir($name) &&  !is_dir($name)) {
      throw new WsdlParserException(sprintf('Directory "%s" was not created', 'output'));
    }
    $this->logger->info('Check or create directory "{name}"', ['name' => $name]);
  }

  private function save($name, $data): void
  {
    @[$ext, $postfix] = explode('=', $name['query']);
    $delim = '_';
    $dirName = trim(str_replace(DIRECTORY_SEPARATOR, $delim, $name['path']), $delim);
    $this->makeDir(implode(DIRECTORY_SEPARATOR, [$this->outputDir, $dirName]));
    $name = implode($delim, [$dirName, $postfix]) . '.' . $ext;
    $filename = trim($name, $delim);
    $fullName = implode(DIRECTORY_SEPARATOR, [$this->outputDir, $dirName, $filename]);
    file_put_contents($fullName, $data);
    $this->logger->info(sprintf('Save "%s"', $fullName));
  }

  private function getNodeList($nodes, $search): ?DOMNodeList
  {
    $match = null;
    /** @var DOMElement $node */
    foreach ($nodes as $node) {
      if ($node->prefix === $search) {
        return $node->childNodes;
      }
      $match = $this->getNodeList($node->childNodes, $search);
      if ($match) {
        break;
      }
    }
    return $match;
  }

  public function parse(string $uri): void
  {
    $name    = parse_url($uri);
    if (!isset($name['scheme'])) {
      return;
    }
    $this->logger->info(sprintf('Downloading "%s"', $uri));

    $wsdl = file_get_contents($uri);
    $this->save($name, $wsdl);

    $dom = new DOMDocument();
    $dom->loadXML($wsdl);
    $nodes = $this->getNodeList($dom->childNodes, 'xsd');
    if ($nodes) {
      /** @var DOMElement $node */
      foreach ($nodes as $node) {
        $this->parse($node->attributes->item(0)->nodeValue);
      }
    }
  }

  public function handle(array $argv): void
  {
    foreach ($argv as $uri) {
      $this->parse($uri);
    }
  }

}
