<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Organization;
use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Repository\OrganizationRepository;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Контроллер для работы с реусрсом организаций
 *
 * @Route("/api/organization", service="api.controller.organization")
 */
class OrganizationController extends ApiController
{
    /**
     * @var OrganizationRepository
     */
    private $organization_repository;

    /**
     * Конструктор контроллера, репозиторий внедряется автоматически через DI
     *
     * @param OrganizationRepository $organization_repository
     */
    public function __construct(OrganizationRepository $organization_repository)
    {
        $this->organization_repository = $organization_repository;
    }

    /**
     * @ApiDoc(
     *     resource="/api/organization",
     *     description="Вывод списка организаций удовлетворяющих заданным критериям: address - поиск по адресу содержащему указанную подстроку, location - поиск организаций находящихся на заданном удалении от указанной точки, rubric - поиск по рубрике, order - сортировка: address, name (по умолчанию)",
     *     filters={
     *          {"name"="address", "dataType"="string"},
     *          {"name"="location", "dataType"="float,float,int", "pattern"="latitude,longitude,radius"},
     *          {"name"="rubric", "dataType"="int"},
     *          {"name"="order", "dataType"="string", "pattern"="name|address"},
     *     },
     *     statusCodes={
     *          200="Успешное выполнение запроса",
     *          400="Неверные параметры запроса",
     *          404="Данные не найдены, отсутствуют орагнизации удовлетворяющие заданным критериям",
     *          500="Внутренняя ошибка сервера"
     *     }
     * )
     *
     * @param Request $request
     * @return array
     *
     * @View(serializerGroups={"list"})
     * @Route("/", name="api_organization_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request)
    {
        $organizations = $this->organization_repository
            ->findByParams($this->filterParams($request, [
                SearchFilters::Q_ADDRESS,
                SearchFilters::Q_LOCATION,
                SearchFilters::Q_RUBRIC,
                SearchFilters::Q_ORDER
            ]));

        return $this->returnResult('organizations', $organizations);
    }

    /**
     * @ApiDoc(
     *     resource="/api/organization",
     *     description="Вывод информации об организации по заданному идентификатору.",
     *     statusCodes={
     *          200="Успешное выполнение запроса",
     *          404="Данные не найдены, организации с указанным идентификатором не существует",
     *          500="Внутренняя ошибка сервера"
     *     }
     * )
     *
     * @param int $id
     * @return array
     *
     * @View(serializerGroups={"details", "organization_details"}, serializerEnableMaxDepthChecks="true")
     * @Route("/{id}", requirements={"id": "\d+"}, name="api_organization_item")
     * @Method({"GET"})
     */
    public function itemAction($id)
    {
        return $this->returnResult('organizations', $this->organization_repository->findById($id));
    }
}
