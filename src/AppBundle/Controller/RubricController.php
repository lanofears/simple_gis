<?php

namespace AppBundle\Controller;

use AppBundle\Extensions\References\SearchFilters;
use AppBundle\Repository\RubricRepository;
use FOS\RestBundle\Controller\Annotations\View;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Контроллер для работы с ресурсом рубрик
 *
 * @Route("/api/rubric", service="api.controller.rubric")
 */
class RubricController extends ApiController
{
    /**
     * @var RubricRepository
     */
    private $rubric_repository;

    /**
     * Конструктор контроллера, репозиторий внедряется автоматически через DI
     *
     * @param RubricRepository $rubric_repository
     */
    public function __construct(RubricRepository $rubric_repository)
    {
        $this->rubric_repository = $rubric_repository;
    }

    /**
     * @ApiDoc(
     *     resource="/api/rubric",
     *     description="Вывод списка рубрик удовлетворяющих заданным критериям: parent - поиск по идентификатору родительской рубрики, name - поиск по наименованию, сортировка по умолчанию - по наименованию",
     *     filters={
     *          {"name"="parent", "dataType"="int"},
     *          {"name"="name", "dataType"="string"},
     *          {"name"="callback", "dataType"="string"},
     *     },
     *     statusCodes={
     *          200="Успешное выполнение запроса",
     *          400="Неверные параметры запроса",
     *          404="Данные не найдены, список рубрик пуст",
     *          500="Внутренняя ошибка сервера"
     *     }
     * )
     *
     * @param Request $request
     * @return array
     *
     * @View(serializerGroups={"list"})
     * @Route("/", name="api_rubric_list")
     * @Method({"GET"})
     */
    public function listAction(Request $request)
    {
        $rubrics = $this->rubric_repository
            ->findByParams($this->filterParams($request, [
                SearchFilters::Q_NAME,
                SearchFilters::Q_PARENT,
                SearchFilters::Q_ORDER
            ]));

        return $this->returnResult('rubrics', $rubrics);
    }

    /**
     * @ApiDoc(
     *     resource="/api/rubric",
     *     description="Вывод информации о рубрике по заданному идентификатору.",
     *     filters={
     *          {"name"="callback", "dataType"="string"},
     *     },
     *     statusCodes={
     *          200="Успешное выполнение запроса",
     *          404="Данные не найдены, организации с указанным идентификатором не существует",
     *          500="Внутренняя ошибка сервера"
     *     }
     * )
     *
     * @param Request $request
     * @param int $id
     * @return array
     *
     * @View(serializerGroups={"details", "rubric_details"}, serializerEnableMaxDepthChecks="true")
     * @Route("/{id}", requirements={"id": "\d+"}, name="api_rubric_item")
     * @Method({"GET"})
     */
    public function itemAction(Request $request,$id)
    {
        $this->filterParams($request, []);

        return $this->returnResult('rubrics', $this->rubric_repository->findById($id));
    }
}