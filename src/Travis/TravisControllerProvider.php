<?php

namespace StpBoard\Travis;

use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Stp\TravisClient\Entities\Build;
use StpBoard\Base\BoardProviderInterface;
use StpBoard\Base\TwigTrait;
use Stp\TravisClient\Client as TravisClient;
use Symfony\Component\HttpFoundation\Request;

class TravisControllerProvider implements ControllerProviderInterface, BoardProviderInterface
{
    use TwigTrait;

    /**
     * Returns route prefix
     * @return string
     */
    public static function getRoutePrefix()
    {
        return '/travis';
    }

    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app)
    {
        $this->initTwig(__DIR__ . '/views');
        $controllers = $app['controllers_factory'];

        $controllers->get(
            '/',
            function (Application $app) {
                /** @var Request $request */
                $request = $app['request'];

                $travisUrl = $request->get('travis_url', null);
                $githubToken = $request->get('github_token');
                $repo = $request->get('repo');
                $type = $request->get('type', 'branch');

                $viewData = [
                    'repo' => $repo,
                    'builds' => [],
                    'limit' => 1
                ];

                try {
                    $client = new TravisClient($githubToken, $travisUrl);

                    switch($type) {
                        default:
                        case 'branch':
                            $branch = $request->get('branch', 'master');
                            $viewData['branch'] = $branch;

                            $data = $client->getBranch($repo, $branch);
                            $viewData['builds'][] = [
                                'state' => $data->getState(),
                                'started_at' => $data->getStartedAt(),
                                'commit' => $data->getCommit(),
                                'author_gravatar' => sprintf('//gravatar.com/avatar/%s', md5($data->getCommit()->getAuthorEmail()))
                            ];
                            break;
                        case 'pullrequests':
                            $limit = filter_var($request->get('limit', 3), FILTER_SANITIZE_NUMBER_INT);
                            $viewData['limit'] = $limit;
                            $pullRequests = $client->getBuilds($repo, ['event_type' => 'pull_request']);
                            foreach ($pullRequests as $pullRequest) {
                                /** @var Build $pullRequest */
                                if (count($viewData['builds']) == $limit) {
                                    break;
                                }

                                $viewData['builds'][] = [
                                    'state' => $pullRequest->getState(),
                                    'started_at' => $pullRequest->getStartedAt(),
                                    'commit' => $pullRequest->getCommit(),
                                    'author_gravatar' => sprintf('//gravatar.com/avatar/%s', md5($pullRequest->getCommit()->getAuthorEmail()))
                                ];
                            }
                            break;
                    }

                    return $this->twig->render('index.html.twig', [
                        'title' => 'Travis',
                        'data' => $viewData
                    ]);
                } catch (\Exception $e) {
                    return $this->twig->render('error.html.twig', [
                        'title' => 'Travis'
                    ]);
                }
            }
        );

        return $controllers;
    }
}
