<?php

namespace App\Command;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use JsonStreamingParser\Listener\GeoJsonListener;
use JsonStreamingParser\Parser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[AsCommand(name: 'app:product-import')]
class ProductImportCommand extends Command
{
    public function __construct(
        private SerializerInterface $serializer,
        private EntityManagerInterface $em,
        private Filesystem $filesystem,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('Import Products.')
            ->setHelp('This command allows you to import Products from JSON file.')
            ->addArgument('filePath', InputArgument::REQUIRED, 'JSON file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import Products');

        $filePath = $input->getArgument('filePath');
        if (!$this->filesystem->exists($filePath)) {
            $io->error(sprintf('File %s not exist.', $filePath));

            return Command::FAILURE;
        }

        if (!filesize($filePath)) {
            $io->error(sprintf('File %s is empty.', $filePath));

            return Command::FAILURE;
        }

        $stream = fopen($filePath, 'r');

        $io->progressStart();
        $productRepository = $this->em->getRepository(Product::class);
        $counter = 0;
        $listener = new GeoJsonListener(function ($item) use ($productRepository, &$counter, $io) {
            if ($item) {
                $context = [];
                if ($existingProduct = $productRepository->findOneBy(['asin' => $item['productASIN']])) {
                    $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $existingProduct;
                }

                $data = json_encode($item);
                $product = $this->serializer->deserialize(
                    $data,
                    Product::class,
                    'json',
                    $context
                );

                $this->em->persist($product);
                ++$counter;

                if ($counter >= 100) {
                    $this->em->flush();
                    $this->em->clear();
                }

                $io->progressAdvance();
            }
        });

        try {
            $parser = new Parser($stream, $listener);
            $parser->parse();
            $this->em->flush();
            fclose($stream);

            $io->progressFinish();
        } catch (\Exception $e) {
            fclose($stream);
            $io->progressFinish();
            $io->error($e->getMessage());
            $io->warning(sprintf('Not all products were imported. Only %d Products were imported.', $counter));

            return Command::FAILURE;
        }

        $io->success(sprintf('%d Products were successfully imported.', $counter));

        return Command::SUCCESS;
    }
}
