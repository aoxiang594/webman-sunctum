<?php

namespace app\command;

use support\Db;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SunctumInitCommand extends Command
{
    protected static $defaultName = 'sunctum:init';
    protected static $defaultDescription = '首次安装时初始化Suncum';

    /**
     * @return void
     */
    protected function configure()
    {
//        $this->addArgument('name', InputArgument::REQUIRED, 'Add name');
    }

    /**
     * @param  InputInterface   $input
     * @param  OutputInterface  $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Check Table Exists');
        $tableExists = false;
        $tableName   = 'personal_access_tokens';
        $result      = DB::select("show tables;");
        $result      = json_decode(json_encode($result), true);
        foreach ($result as $item) {
            if( array_search($tableName, $item) !== false ){
                $tableExists = true;
                break;
            }
        }


        if( !$tableExists ){
            $output->writeln("Table Not Exists,Start Create Table");
            Db::select("CREATE TABLE `" . $tableName . "` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
            $output->writeln("Install Webman Sunctum Success");
        } else {
            $output->writeln("Table Exists,Dont`t Create Repeatedly");
        }


        return self::SUCCESS;
    }

}