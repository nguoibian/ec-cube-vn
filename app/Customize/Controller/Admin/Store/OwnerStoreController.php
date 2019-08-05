<?php
namespace Customize\Controller\Admin\Store;

use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Eccube\Controller\AbstractController;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\PluginService;
use Eccube\Repository\PluginRepository;
use Eccube\Repository\TemplateRepository;
use Customize\Service\HttpClient;
use Customize\Service\TemplateService;

class OwnerStoreController extends AbstractController
{
    /**
     * @var BaseInfoRepository
     */
    protected $baseInfo;

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var PluginService
     */
    protected $pluginService;

    /**
     * @var TemplateService
     */
    protected $templateService;

    /**
     * @var PluginRepository
     */
    protected $pluginRepository;

    /**
     * @var TemplateRepository
     */
    protected $templateRepository;

    /**
     * OwnerStoreController constructor.
     * @param HttpClient $httpClient
     * @param BaseInfoRepository $baseInfoRepository
     * @param PluginService $pluginService
     * @param TemplateService $templateService
     * @param PluginRepository $pluginRepository
     * @param TemplateRepository $templateRepository
     * @throws \Exception
     */
    public function __construct(
        HttpClient $httpClient,
        BaseInfoRepository $baseInfoRepository,
        PluginService $pluginService,
        TemplateService $templateService,
        PluginRepository $pluginRepository,
        TemplateRepository $templateRepository
    ) {
        $this->httpClient = $httpClient;
        $this->baseInfo = $baseInfoRepository->get();
        $this->pluginService = $pluginService;
        $this->templateService = $templateService;
        $this->pluginRepository = $pluginRepository;
        $this->templateRepository = $templateRepository;
    }

    /**
     * This action is replace default See \Eccube\Controller\Admin\Store\OwnerStoreController::search
     *
     * @Route("/%eccube_admin_route%/store/plugin/api/search", name="admin_store_plugin_owners_search")
     * @Route("/%eccube_admin_route%/store/plugin/api/search/page/{page_no}", name="admin_store_plugin_owners_search_page", requirements={"page_no" = "\d+"})
     * @Template("@Customize/Admin/Store/OwnerStore/searchPlugin.twig")
     *
     * @return array
     */
    public function searchPlugin(Request $request, $page_no = null, Paginator $paginator)
    {
        $endpoint = $this->baseInfo->getOwnerStoreApiEndpoint();
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer '. $this->baseInfo->getOwnerStoreAuthToken()
        ];
        $installedPlugins = [];
        foreach ($this->pluginRepository->findAll() as $plugin) {
            $installedPlugins[$plugin->getCode()] = $plugin->getVersion();
        }

        $categoriesResult = $this->httpClient->request($endpoint. '/api/v1/plugins/categories', $headers);
        $pluginsResult = $this->httpClient->request($endpoint . '/api/v1/plugins?coreVersion=' . \Eccube\Common\Constant::VERSION, $headers);

        return [
            'categoriesAsJson' => $categoriesResult,
            'pluginsAsJson' => $pluginsResult,
            'installedPluginsAsJson' => json_encode($installedPlugins)
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/store/plugin/api/ajax", name="admin_store_plugin_owners_ajax", methods={"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxPlugin(Request $request)
    {
        $endpoint = $this->baseInfo->getOwnerStoreApiEndpoint() . '/api/v1/plugins';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer '. $this->baseInfo->getOwnerStoreAuthToken()
        ];
        $queryParams = [
            "coreVersion" => \Eccube\Common\Constant::VERSION,
        ];

        $categories = $request->get('categories', '');
        if ($categories) {
            $queryParams['categoryIds'] = implode('|', $categories);
        }

        $keyword = $request->get('keyword', '');
        if ($keyword) {
            $queryParams['keyword'] = $keyword;
        }

        $pageNum = $request->get('pageNum', 0);
        if ($pageNum) {
            $queryParams['pageNum'] = $pageNum;
        }

        $pageSize = $request->get('pageSize', 10);
        if ($pageSize) {
            $queryParams['pageSize'] = $pageSize;
        }

        $price = $request->get('price', []);
        if ($price && count($price) == 1) {
            $queryParams['price'] = ($price[0] == 'free') ? '0|0' : '1|' . PHP_INT_MAX;
        }

        if ($queryParams) {
            $endpoint .= '?' . http_build_query($queryParams);
        }

        $result = $this->httpClient->request($endpoint, $headers);

        return new JsonResponse($result, 200, [], true);
    }

    /**
     *
     * @Route("/%eccube_admin_route%/store/template/api/search", name="customize_store_template_owners_search")
     * @Template("@Customize/Admin/Store/OwnerStore/searchTemplate.twig")
     *
     * @return array
     */
    public function searchTemplate()
    {
        $endpoint = $this->baseInfo->getOwnerStoreApiEndpoint();
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer '. $this->baseInfo->getOwnerStoreAuthToken()
        ];
        $categoriesResult = $this->httpClient->request($endpoint . '/api/v1/templates/categories', $headers);
        $themesResult = $this->httpClient->request($endpoint . '/api/v1/templates?coreVersion=' . \Eccube\Common\Constant::VERSION, $headers);

        $installedTemplates = [];
        foreach ($this->templateRepository->findAll() as $template) {
            $installedTemplates[$template->getCode()] = "1.0.0";
        }

        return [
            'categoriesAsJson' => $categoriesResult,
            'templatesAsJson' => $themesResult,
            'installedTemplatesDataAsJson' => json_encode($installedTemplates)
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/store/template/api/ajax", name="customize_store_template_owners_ajax", methods={"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ajaxTemplate(Request $request)
    {
        $endpoint = $this->baseInfo->getOwnerStoreApiEndpoint() . '/api/v1/templates';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer '. $this->baseInfo->getOwnerStoreAuthToken()
        ];
        $queryParams = [
            "coreVersion" => \Eccube\Common\Constant::VERSION
        ];

        $categories = $request->get('categories', '');
        if ($categories) {
            $queryParams['categoryIds'] = implode('|', $categories);
        }

        $keyword = $request->get('keyword', '');
        if ($keyword) {
            $queryParams['keyword'] = $keyword;
        }

        $pageNum = $request->get('pageNum', 0);
        if ($pageNum) {
            $queryParams['pageNum'] = $pageNum;
        }

        $pageSize = $request->get('pageSize', 10);
        if ($pageSize) {
            $queryParams['pageSize'] = $pageSize;
        }

        $price = $request->get('price', []);
        if ($price && count($price) == 1) {
            $queryParams['price'] = ($price[0] == 'free') ? '0|0' : '1|' . PHP_INT_MAX;
        }

        if ($queryParams) {
            $endpoint .= '?' . http_build_query($queryParams);
        }

        $result = $this->httpClient->request($endpoint, $headers);

        return new JsonResponse($result, 200, [], true);
    }

    /**
     * @Route("/%eccube_admin_route%/store/plugin/api/ajax/install", name="customize_store_plugin_owners_ajax_install", methods={"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Eccube\Exception\PluginException
     * @throws \ErrorException
     */
    public function installPlugin(Request $request)
    {
        $result = false;
        $packageUrl = $request->get("packageUrl", null);
        if ($packageUrl) {
            $headers = [
                'Authorization: Bearer '. $this->baseInfo->getOwnerStoreAuthToken()
            ];
            try {
                $filePath = $this->httpClient->download($packageUrl, $headers);
                $result = (bool)$this->pluginService->install($filePath);
            } catch (\Exception $e) {
                log_error(__METHOD__, [$e]);
                $result = false;
            }
        }

        return new JsonResponse(["result" =>  $result]);
    }

    /**
     * @Route("/%eccube_admin_route%/store/template/api/ajax/install", name="customize_store_template_owners_ajax_install", methods={"POST"})
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Eccube\Exception\PluginException
     * @throws \ErrorException
     */
    public function installTemplate(Request $request)
    {
        $packageUrl = $request->get("packageUrl", null);
        $code = $request->get("code", null);
        $name = $request->get("name", null);
        $result = false;
        if ($packageUrl && $code & $name) {
            $headers = [
                'Authorization: Bearer '. $this->baseInfo->getOwnerStoreAuthToken()
            ];
            try {
                $filePath = $this->httpClient->download($packageUrl, $headers);
                $result = (bool)$this->templateService->install($filePath, $name, $code);
            } catch (\Exception $e) {
                log_error(__METHOD__, [$e]);
                $result = false;
            }
        }
        return new JsonResponse(["result" =>  $result]);
    }
}
