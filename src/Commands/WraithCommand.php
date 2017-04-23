<?php

namespace TerminusPluginProject\TerminusWraith\Commands;

use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Yaml\Yaml;

/**
 * Assist with visual regression testing of Pantheon site environments.
 */
class WraithCommand extends TerminusCommand implements SiteAwareInterface
{

    use SiteAwareTrait;

    /**
     * Visual regression testing with Wraith.
     *
     * @authorize
     *
     * @command wraith
     *
     * @option array $sites A comma separated list of site environments to compare in `site-name.env` format. Example: my-site.test,my-site.prod
     * @option array $paths A comma separated list of name=value relative paths to compare. Example: home=/,about=/about,news=/news,etc.
     * @option bool $config Enable configuration mode
     * @option bool $spider Crawl to detect pages
     *
     * @usage [--sites site-name.test,site-name.prod --paths home=/,about=/about,news=/news,... --config --spider]
     *     Generate snapshots to visually compare various pages of different site environments.
     */
    public function wraith($options = ['sites' => [], 'paths' => [], 'config' => false, 'spider' => false]) {

        $this->checkRequirements();

        // Determine if capture configuration exists.
        $capture_file = __DIR__ . '/configs/capture.yaml';
        if (!file_exists($capture_file)) {
            $options['config'] = true;
            exec('wraith setup', $messages, $return_var);
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $this->io()->writeln($message);
                }
            }
        }

        // Override --config option if --no-interaction is set.
        if ($options['no-interaction']) {
            $options['config'] = false;
        }

        // Configuration requested.
        if ($options['config']) {
            // Extract capture data.
            $capture_data = $this->getYaml($capture_file);

            // Get the sites to compare.
            if (empty($options['sites'])) {
                $creds = array();
                $site_envs = array();
                $site_envs[] = $this->io()->ask('Enter the source in `site-name.env` format');
                $login = $this->io()->confirm('Does this environment require a login?');
                if (!$login) {
                    $creds[$site_envs[0]] = '';
                } else {
                    $user = $this->io()->ask('Enter the username');
                    $pass = $this->io()->askHidden('Enter the password');
                    $creds[$site_envs[0]] = "${user}:${pass}@";
                }
                $site_envs[] = $this->io()->ask('Enter the target in `site-name.env` format');
                $login = $this->io()->confirm('Does this environment require a login?');
                if (!$login) {
                    $creds[$site_envs[1]] = '';
                } else {
                    $user = $this->io()->ask('Enter the username');
                    $pass = $this->io()->askHidden('Enter the password');
                    $creds[$site_envs[1]] = "${user}:${pass}@";
                }
            } else {
                $site_envs = explode(',', $options['sites'][0]);
            }

            // Get the site domains.
            $domains = array();
            foreach ($site_envs as $site_env) {
                list(, $env) = $this->getSiteEnv($site_env);
                $domain_info = $env->getDomains()->serialize();
                foreach ($domain_info as $info) {
                    $domains[$info['environment']] = 'http://' . $creds[$site_env] . $info['domain'];
                }
            }
            $capture_data['domains'] = $domains;

            // Get the site paths.
            if (!$options['spider']) {
                $paths = array();
                if (empty($options['paths'])) {
                    $path = $options['yes'] ? '' : '/';
                    while ($path) {
                        if ($name = $this->io()->ask('Enter the name for a relative path or `0` to cancel', 0)) {
                            if ($path = $this->io()->ask('Enter the url of the relative path or `0` to cancel', 0)) {
                                $paths[$name] = $path;
                            }
                        } else {
                            $path = 0;
                        }
                    }
                    // Need at least one page to compare.
                    if (empty($paths)) {
                        $paths['home'] = '/';
                    }
                } else {
                    $pairs = explode(',', $options['paths'][0]);
                    foreach ($pairs as $pair) {
                        $values = explode('=', $pair);
                        $paths[$values[0]] = $values[1];
                    }
                }
                if (isset($capture_data['imports'])) {
                    unset($capture_data['imports']);
                }
                $capture_data['paths'] = $paths;
            }

            // Output the new configuration.
            $this->putYaml($capture_file, $capture_data);
        }

        // Crawl to detect pages.
        if ($options['spider']) {
            $capture_data = $this->getYaml($capture_file);
            if (isset($capture_data['paths'])) {
                unset($capture_data['paths']);
            }
            $capture_data['imports'] = 'spider_paths.yaml';
            $this->putYaml($capture_file, $capture_data);
            exec('wraith spider ' . $capture_file, $messages, $return_var);
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $this->io()->writeln($message);
                }
            }
        }

        // Generate snapshots.
        exec('wraith capture ' . $capture_file, $messages, $return_var);
        if (!empty($messages)) {
            foreach ($messages as $message) {
                $this->io()->writeln($message);
            }
        }

    }

    /**
     * Platform independent check whether a command exists.
     *
     * @param string $command Command to check
     * @return bool True if exists, false otherwise
     */
    protected function commandExists($command)
    {
        $windows = (php_uname('s') == 'Windows NT');
        $test_command = $windows ? 'where' : 'command -v';
        $file = popen("$test_command $command", 'r');
        $result = fgets($file, 255);
        return $windows ? !preg_match('#Could not find files#', $result) : !empty($result);
    }

    /**
     * Check for plugin requirements.
     */
    protected function checkRequirements()
    {
        if (!$this->commandExists('wraith')) {
            $message = 'Please install Wraith to enable visual regression testing.  See http://bbc-news.github.io/wraith/os-install.html.';
            throw new TerminusNotFoundException($message);
        }
    }

    /**
     * Extract YAML data.
     *
     * @param string $yaml_file The YAML file
     * @return array An array of YAML compatible data
     */
    protected function getYaml($yaml_file)
    {
        if ($yaml_data = @file_get_contents($yaml_file)) {
            return Yaml::parse($yaml_data);
        }
        return array();
    }

    /**
     * Save YAML data.
     *
     * @param string $yaml_file The YAML file
     * @param array $yaml_data An array of YAML compatible data
     */
    protected function putYaml($yaml_file, $yaml_data)
    {
        if (!$yaml_formatted_data = Yaml::dump($yaml_data)) {
            throw new TerminusNotFoundException('Unable to save configuration.  Invalid YAML data.');
        }
        try {
            $handle = fopen($yaml_file, 'w');
            fwrite($handle, $yaml_formatted_data);
            fclose($handle);
        } catch (Exception $e) {
            throw new TerminusNotFoundException($e->getMessage());
        }
    }
}
