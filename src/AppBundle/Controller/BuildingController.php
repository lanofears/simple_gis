<?php

namespace AppBundle\Controller;

use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Repository\BuildingRepository;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Контроллер для работы с ресурсом зданий
 *
 * @Route("/api/building", service="api.controller.building")
 */
class BuildingController extends ApiController
{
    private $building_repository;

    /**
     * Конструктор контроллера, репозиторий внедряется автоматически через DI
     *
     * @param $building_repository
     */
    public function __construct(BuildingRepository $building_repository)
    {
        $this->building_repository = $building_repository;
    }

    /**
     * @ApiDoc(
     *     resource="/api/building",
     *     description="Выод списка зданий удовлетворяющих заданным критериям: address - поиск по адресу содержащему указанную подсроку, location - поиск зданий находящихся на заданном удалении от указанной точки",
     *     filters={
     *          {"name"="address", "dataType"="string"},
     *          {"name"="location", "dataType"="float,float,int", "pattern"="latitude,longitude,radius"},
     *     },
     *     statusCodes={
     *          200="Успешное выполнение запроса",
     *          400="Неверные параметры запроса",
     *          404="Данные не найдены, здания с указанным идентификатором не существует",
     *          500="Внутренняя ошибка сервера"
     *     }
     * )
     *
     * @param Request $request
     * @return array
     *
     * @View(serializerGroups={"list"})
     * @Route("/", name="api_building_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request)
    {
        $buildings = $this->building_repository
            ->findByParams($this->filterParams($request, [
                SearchFilters::Q_ADDRESS,
                SearchFilters::Q_LOCATION
            ]));

        return $this->returnResult('buildings', $buildings);
    }

    /**
     * @ApiDoc(
     *     resource="/api/building",
     *     description="Вывод информации о здании по заданному идентификатору.",
     *     statusCodes={
     *          200="Успешное выполнение запроса",
     *          404="Данные не найдены, здания с указанным идентификатором не существует",
     *          500="Внутренняя ошибка сервера"
     *     }
     * )
     *
     * @param int $id
     * @return array
     *
     * @View(serializerGroups={"details", "building_details"})
     * @Route("/{id}", requirements={"id": "\d+"}, name="api_building_item")
     * @Method({"GET"})
     */
    public function itemAction($id)
    {
        return $this->returnResult('buildings', [ $this->building_repository->find($id) ]);
    }
}
