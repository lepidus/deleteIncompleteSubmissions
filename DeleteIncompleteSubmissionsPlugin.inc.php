<?php

import('lib.pkp.classes.plugins.GenericPlugin');

class DeleteIncompleteSubmissionsPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            HookRegistry::register('TemplateManager::display', [$this, 'addIncompleteSubmissionsTab']);
        }

        return $success;
    }

    public function getDisplayName(): string
    {
        return __('plugins.generic.deleteIncompleteSubmissions.displayName');
    }

    public function getDescription(): string
    {
        return __('plugins.generic.deleteIncompleteSubmissions.description');
    }

    public function getCanEnable()
    {
        return ((bool) Application::get()->getRequest()->getContext());
    }

    public function getCanDisable()
    {
        return ((bool) Application::get()->getRequest()->getContext());
    }

    public function addIncompleteSubmissionsTab($hookName, $params)
    {
        $templateMgr = $params[0];
        $template = $params[1];

        if ($template !== 'dashboard/index.tpl') {
            return false;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $dispatcher = $request->getDispatcher();
        $apiUrl = $dispatcher->url($request, ROUTE_API, $context->getPath(), '_submissions');

        $componentsState = $templateMgr->getState('components');
        $userRoles = $templateMgr->get_template_vars('userRoles');

        $includeAssignedEditorsFilter = array_intersect([ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER], $userRoles);
        $includeIssuesFilter = array_intersect(
            [ROLE_ID_SITE_ADMIN, ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT],
            $userRoles
        );

        $this->loadResources($request, $templateMgr);

        $incompleteListPanel = new \APP\components\listPanels\SubmissionsListPanel(
            'incompleteSubmissions',
            __('plugins.generic.deleteIncompleteSubmissions.incompleteSubmissionsTab'),
            [
                'apiUrl' => $apiUrl,
                'getParams' => [
                    'isIncomplete' => true,
                ],
                'lazyLoad' => true,
                'includeIssuesFilter' => $includeIssuesFilter,
                'includeAssignedEditorsFilter' => $includeAssignedEditorsFilter,
                'includeActiveSectionFiltersOnly' => true,
            ]
        );
        $componentsState[$incompleteListPanel->id] = $incompleteListPanel->getConfig();

        $templateMgr->setState(['components' => $componentsState]);


        $templateMgr->registerFilter("output", array($this, 'incompleteSubmissionsTabFilter'));

        return false;
    }

    public function incompleteSubmissionsTabFilter($output, $templateMgr)
    {
        if (preg_match('/<\/tab[^>]+>/', $output, $matches, PREG_OFFSET_CAPTURE)) {
            $match = $matches[0][0];
            $offset = $matches[0][1];

            $newOutput = substr($output, 0, $offset);
            $newOutput .= $templateMgr->fetch($this->getTemplateResource('incompleteSubmissionsTab.tpl'));
            $newOutput .= substr($output, $offset);
            $output = $newOutput;
            $templateMgr->unregisterFilter('output', array($this, 'incompleteSubmissionsTabFilter'));
        }
        return $output;
    }

    private function loadResources($request, $templateMgr)
    {
        $pluginFullPath = $request->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath();

        $templateMgr->addJavaScript(
            'incomplete-submissions-list-item',
            $pluginFullPath . '/js/components/IncompleteSubmissionsListItem.js',
            [
                'priority' => STYLE_SEQUENCE_LAST,
                'contexts' => ['backend']
            ]
        );
    }
}
