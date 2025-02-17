<?php

import('classes.handler.Handler');
import('plugins.generic.thoth.classes.components.listPanels.ThothListPanel');

class ThothHandler extends Handler
{
    public $_isBackendPage = true;

    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment(
            array(ROLE_ID_SUB_EDITOR, ROLE_ID_MANAGER),
            array('index')
        );
    }

    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    public function initialize($request)
    {
        $this->setupTemplate($request);
        parent::initialize($request);
    }

    public function index($args, $request)
    {
        AppLocale::requireComponents(LOCALE_COMPONENT_APP_SUBMISSION);
        $context = $request->getContext();
        $connectionError = false;

        $plugin = PluginRegistry::getPlugin('generic', 'thothplugin');
        $templateMgr = TemplateManager::getManager($request);

        $this->addScripts($request, $templateMgr, $plugin);
        $this->addStyles($request, $templateMgr, $plugin);

        try {
            $publishers = ThothRepo::account()->getLinkedPublishers();
            $imprints = ThothRepo::imprint()->getMany(array_column($publishers, 'publisherId'));
        } catch (Exception $e) {
            error_log($e->getMessage());
            $connectionError = true;
        }

        $imprintOptions = [];
        foreach ($imprints as $imprint) {
            $imprintOptions[] = [
                'value' => $imprint->getImprintId(),
                'label' => $imprint->getImprintName()
            ];
        }

        $selectedImprint = null;
        if (count($imprintOptions) === 1) {
            $selectedImprint = $imprintOptions[0]['value'];
        }

        $thothList = new ThothListPanel(
            'thoth',
            __('submission.list.monographs'),
            [
                'apiUrl' => $request->getDispatcher()->url(
                    $request,
                    ROUTE_API,
                    $context->getPath(),
                    'submissions'
                ),
                'imprintOptions' => $imprintOptions,
                'selectedImprint' => $selectedImprint
            ]
        );

        $submissionService = \Services::get('submission');
        $params = array_merge($thothList->getParams, [
            'count' => $thothList->count,
            'contextId' => $context->getId(),
        ]);
        $submissionsIterator = $submissionService->getMany($params);
        $items = [];
        foreach ($submissionsIterator as $submission) {
            $items[] = $submissionService->getBackendListProperties($submission, ['request' => $request]);
        }
        $thothList->set([
            'items' => $items,
            'itemsMax' => $submissionService->getMax($params),
        ]);

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->setState([
            'components' => [
                'thoth' => $thothList->getConfig()
            ],
            'connectionError' => $connectionError
        ]);

        return $templateMgr->display($plugin->getTemplateResource('thoth/index.tpl'));
    }

    private function addScripts($request, $templateMgr, $plugin)
    {
        $templateMgr->addJavaScript(
            'thoth-list-item-component',
            $request->getBaseUrl() . '/' .
            $plugin->getPluginPath() .
            '/js/ui/components/ListPanel/ThothListItem.js',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );

        $templateMgr->addJavaScript(
            'thoth-list-panel-component',
            $request->getBaseUrl() . '/' .
            $plugin->getPluginPath() .
            '/js/ui/components/ListPanel/ThothListPanel.js',
            [
                'contexts' => 'backend',
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );
    }

    private function addStyles($request, $templateMgr, $plugin)
    {
        $templateMgr->addStyleSheet(
            'thoth-list-panel-component',
            '
                .listPanel--thoth .listPanel__itemIdentity {
                    position: relative;
                    padding: 0;
                }

                .listPanel__selectAllWrapper {
                    display: flex;
                    align-items: center;
                    margin-top: 1rem;
                    margin-left: -0.5rem;
                    line-height: 1.5rem;

                    > input {
                        margin-left: 0.5rem;
                    }
                }

                .listPanel__selectAllLabel {
                    margin-left: 0.5rem;
                }

                .listPanel__selectWrapper {
                    display: flex;
                    align-items: center;
                    margin-left: -1rem;
                    overflow: hidden;
                }

                .listPanel__selector {
                    width: 3rem;
                    padding-left: 1rem;
                }

                .listPanel__item--thoth_notice {
                    margin-top: .5em;
                    font-size: .75rem;
                    line-height: 1.5em;
                    color: #222;
                }

                .listPanel__itemMetadata {
                    font-size: .75rem;
                    line-height: 1.5em;
                    color: #222;
                    margin-left: 0.75rem;
                }

                .listPanel__itemMetadata--badge {
                    margin-right: 0.25rem;
                }

                .listPanel__itemExpanded--thoth, .listPanel__item--thoth_notice {
                    padding-left: 2rem;
                }

                .listPanel__block--option {
                    display: block;
                }
            ',
            [
                'contexts' => 'backend',
                'inline' => true,
                'priority' => STYLE_SEQUENCE_LAST,
            ]
        );
    }
}
