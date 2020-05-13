<?php
/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Managedinstalls_model::class, function (Faker\Generator $faker) {

    $type = $faker->randomElement(['munki', 'applesus']);
    
    switch ($type) {
        case 'munki':
            $installs = [
                ["chrome", "Google Chrome"],
                ["firefox", "Firefox"],
                ["bcm", "Bitcoin Miner 2000"],
                ["spotify", "Spotify"],
                ["slack", "Slack"],
                ["finalcutpro", "Final Cut Pro"],
                ["discord", "Discord"],
            ];
            break;
        default:
            $installs = [
                ["jkhsdf-111", "Security update"],
                ["jkhsdf-343", "Security update"],
                ["jkhsdf-332", "Supplemental update"],
                ["jkhsdf-445", "Supplemental supplemental update"],
            ];
            break;
    }

    list($name, $display_name) = $faker->randomElement($installs);

    return [
        'name' => $name,
        'display_name' => $display_name,
        'version' => $faker->randomElement(['10.12.3', '9.3.1', '4.5.12', '10.1.1']) ,
        'size' => $faker->numberBetween(1000000, 10000000),
        'installed' => $faker->numberBetween(1000000, 10000000),
        'status' => $faker->randomElement(['install_failed', 'install_succeeded', 'installed', 'pending_install', 'pending_removal', 'removed', 'uninstall_failed', 'uninstalled']),
        'type' => $type,
    ];
});
