<?php
/**
 * Building Survey Controller
 * Apartman/Site yönetimi - Anket kontrolcüsü
 */

class BuildingSurveyController
{
    private $surveyModel;
    private $buildingModel;
    private $questionModel;
    private $responseModel;

    public function __construct()
    {
        $this->surveyModel = new BuildingSurvey();
        $this->buildingModel = new Building();
        $this->questionModel = new SurveyQuestion();
        $this->responseModel = new SurveyResponse();
    }

    /**
     * Anket listesi
     */
    public function index()
    {
        Auth::require();

        $page = (int)($_GET['page'] ?? 1);
        $buildingId = $_GET['building_id'] ?? null;
        $status = $_GET['status'] ?? '';
        $surveyType = $_GET['survey_type'] ?? '';

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;
        if ($status) $filters['status'] = $status;
        if ($surveyType) $filters['survey_type'] = $surveyType;

        $surveys = $this->surveyModel->all($filters, $limit, $offset);
        $total = $this->surveyModel->all($filters);
        $total = is_array($total) ? count($total) : 0;
        $pagination = Utils::paginate($total, $limit, $page);

        $buildings = $this->buildingModel->active();

        try {
            echo View::renderWithLayout('surveys/index', [
                'title' => 'Anketler',
                'surveys' => $surveys ?: [],
                'pagination' => $pagination,
                'buildings' => $buildings ?: [],
                'filters' => $filters
            ]);
        } catch (Exception $e) {
            error_log("BuildingSurveyController::index() error: " . $e->getMessage());
            Utils::flash('error', Utils::safeExceptionMessage($e, 'Sayfa yüklenirken bir hata oluştu'));
            redirect(base_url('/'));
        }
    }

    /**
     * Anket detay
     */
    public function show($id)
    {
        Auth::require();

        $survey = $this->surveyModel->find($id);
        if (!$survey) {
            Utils::flash('error', 'Anket bulunamadı');
            redirect(base_url('/surveys'));
        }

        // Soruları getir
        $questions = $this->surveyModel->getQuestions($id);

        // Cevapları getir
        $responses = $this->responseModel->listBySurvey($id, 100, 0);

        try {
            echo View::renderWithLayout('surveys/show', [
                'title' => $survey['title'],
                'survey' => $survey,
                'questions' => $questions ?: [],
                'responses' => $responses ?: []
            ]);
        } catch (Exception $e) {
            error_log("BuildingSurveyController::show() error: " . $e->getMessage());
            Utils::flash('error', 'Sayfa yüklenirken hata oluştu');
            redirect(base_url('/surveys'));
        }
    }

    /**
     * Yeni anket formu
     */
    public function create()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('surveys/form', [
            'title' => 'Yeni Anket Oluştur',
            'survey' => null,
            'buildings' => $buildings,
            'buildingId' => $buildingId
        ]);
    }

    /**
     * Anket kaydet
     */
    public function store()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/surveys'));
        }

        try {
            $data = [
                'building_id' => (int)($_POST['building_id'] ?? 0),
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'survey_type' => $_POST['survey_type'] ?? 'poll',
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => $_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days')),
                'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
                'allow_multiple' => isset($_POST['allow_multiple']) ? 1 : 0,
                'status' => $_POST['status'] ?? 'draft',
                'created_by' => Auth::id()
            ];

            if (empty($data['building_id'])) {
                throw new Exception('Bina seçilmelidir');
            }

            if (empty($data['title'])) {
                throw new Exception('Başlık gereklidir');
            }

            $surveyId = $this->surveyModel->create($data);

            // Soruları ekle
            if (!empty($_POST['questions']) && is_array($_POST['questions'])) {
                foreach ($_POST['questions'] as $index => $question) {
                    if (empty($question['question_text'])) continue;

                    $questionData = [
                        'survey_id' => $surveyId,
                        'question_text' => $question['question_text'],
                        'question_type' => $question['question_type'] ?? 'single',
                        'options' => !empty($question['options']) ? json_encode(array_filter(explode("\n", $question['options']))) : null,
                        'is_required' => isset($question['is_required']) ? 1 : 0,
                        'display_order' => $index
                    ];
                    $this->questionModel->create($questionData);
                }
            }

            ActivityLogger::log('survey.created', 'building_survey', $surveyId);
            Utils::flash('success', 'Anket başarıyla oluşturuldu');
            redirect(base_url("/surveys/{$surveyId}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/surveys/create'));
        }
    }

    /**
     * Anket düzenleme formu
     */
    public function edit($id)
    {
        Auth::require();

        $survey = $this->surveyModel->find($id);
        if (!$survey) {
            Utils::flash('error', 'Anket bulunamadı');
            redirect(base_url('/surveys'));
        }

        $questions = $this->surveyModel->getQuestions($id);
        $buildings = $this->buildingModel->active();

        echo View::renderWithLayout('surveys/form', [
            'title' => 'Anket Düzenle',
            'survey' => $survey,
            'questions' => $questions ?: [],
            'buildings' => $buildings
        ]);
    }

    /**
     * Anket güncelle
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/surveys'));
        }

        try {
            $data = [
                'title' => $_POST['title'] ?? '',
                'description' => $_POST['description'] ?? '',
                'survey_type' => $_POST['survey_type'] ?? 'poll',
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => $_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days')),
                'is_anonymous' => isset($_POST['is_anonymous']) ? 1 : 0,
                'allow_multiple' => isset($_POST['allow_multiple']) ? 1 : 0,
                'status' => $_POST['status'] ?? 'draft'
            ];

            $this->surveyModel->update($id, $data);

            ActivityLogger::log('survey.updated', 'building_survey', $id);
            Utils::flash('success', 'Anket başarıyla güncellendi');
            redirect(base_url("/surveys/{$id}"));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url("/surveys/{$id}/edit"));
        }
    }

    /**
     * Anket sil
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/surveys'));
        }

        try {
            $this->surveyModel->delete($id);
            ActivityLogger::log('survey.deleted', 'building_survey', $id);
            Utils::flash('success', 'Anket başarıyla silindi');
            redirect(base_url('/surveys'));

        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/surveys'));
        }
    }

    /**
     * Anket yayınla
     */
    public function publish($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/surveys'));
        }

        try {
            $this->surveyModel->update($id, ['status' => 'active']);
            ActivityLogger::log('survey.published', 'building_survey', $id);
            Utils::flash('success', 'Anket yayınlandı');
            redirect(base_url("/surveys/{$id}"));
        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/surveys'));
        }
    }

    /**
     * Anket kapat
     */
    public function close($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/surveys'));
        }

        try {
            $this->surveyModel->update($id, ['status' => 'closed']);
            ActivityLogger::log('survey.closed', 'building_survey', $id);
            Utils::flash('success', 'Anket kapatıldı');
            redirect(base_url("/surveys/{$id}"));
        } catch (Exception $e) {
            Utils::flash('error', Utils::safeExceptionMessage($e));
            redirect(base_url('/surveys'));
        }
    }
}

