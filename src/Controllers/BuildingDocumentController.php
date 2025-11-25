<?php

/**
 * Building Document Controller
 */
class BuildingDocumentController
{
    private $documentModel;
    private $buildingModel;
    private $unitModel;
    private $fileUploadService;

    public function __construct()
    {
        $this->documentModel = new BuildingDocument();
        $this->buildingModel = new Building();
        $this->unitModel = new Unit();
        $this->fileUploadService = FileUploadService::getInstance();
    }

    /**
     * Document list
     */
    public function index()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $unitId = $_GET['unit_id'] ?? null;
        $documentType = $_GET['document_type'] ?? '';
        $page = (int)($_GET['page'] ?? 1);

        $filters = [];
        if ($buildingId) $filters['building_id'] = $buildingId;
        if ($unitId) $filters['unit_id'] = $unitId;
        if ($documentType) $filters['document_type'] = $documentType;

        $limit = 20;
        $offset = ($page - 1) * $limit;

        $documents = $this->documentModel->list($filters, $limit, $offset);
        $total = count($documents);
        $pagination = Utils::paginate($total, $limit, $page);

        $buildings = $this->buildingModel->active();
        $units = $buildingId ? $this->unitModel->getByBuilding($buildingId) : [];

        echo View::renderWithLayout('documents/index', [
            'title' => 'Bina Dokümanları',
            'documents' => $documents,
            'pagination' => $pagination,
            'filters' => $filters,
            'buildings' => $buildings,
            'units' => $units
        ]);
    }

    /**
     * Upload form
     */
    public function upload()
    {
        Auth::require();

        $buildingId = $_GET['building_id'] ?? null;
        $unitId = $_GET['unit_id'] ?? null;

        $buildings = $this->buildingModel->active();
        $units = $buildingId ? $this->unitModel->getByBuilding($buildingId) : [];

        echo View::renderWithLayout('documents/upload', [
            'title' => 'Doküman Yükle',
            'buildingId' => $buildingId,
            'unitId' => $unitId,
            'buildings' => $buildings,
            'units' => $units
        ]);
    }

    /**
     * Process upload
     */
    public function processUpload()
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/documents'));
        }

        try {
            if (empty($_FILES['file'])) {
                throw new Exception('Dosya seçilmelidir');
            }

            $file = $_FILES['file'];
            $buildingId = (int)($_POST['building_id'] ?? 0);
            $unitId = !empty($_POST['unit_id']) ? (int)$_POST['unit_id'] : null;
            $documentType = $_POST['document_type'] ?? 'other';
            $title = $_POST['title'] ?? '';
            $description = $_POST['description'] ?? '';
            $isPublic = isset($_POST['is_public']);

            if (empty($buildingId)) {
                throw new Exception('Bina seçilmelidir');
            }

            if (empty($title)) {
                $title = pathinfo($file['name'], PATHINFO_FILENAME);
            }

            // Use FileUploadService for secure upload
            $fileId = $this->fileUploadService->handleUpload(
                $file, 
                'building_document', 
                $buildingId, 
                Auth::id(), 
                $isPublic
            );

            // Get uploaded file info
            $uploadedFile = $this->fileUploadService->getFile($fileId);

            // Create building document record
            $documentId = $this->documentModel->create([
                'building_id' => $buildingId,
                'unit_id' => $unitId,
                'document_type' => $documentType,
                'title' => $title,
                'description' => $description,
                'file_path' => $uploadedFile['file_path'],
                'file_name' => $uploadedFile['file_name'],
                'file_size' => $uploadedFile['file_size'],
                'mime_type' => $uploadedFile['mime_type'],
                'is_public' => $isPublic,
                'uploaded_by' => Auth::id()
            ]);

            ActivityLogger::log('document.uploaded', 'building_document', $documentId, [
                'title' => $title,
                'type' => $documentType,
                'building_id' => $buildingId
            ]);

            Utils::flash('success', 'Doküman başarıyla yüklendi');
            redirect('/documents?building_id=' . $buildingId);

        } catch (Exception $e) {
            Utils::flash('error', 'Yükleme hatası: ' . Utils::safeExceptionMessage($e));
            redirect('/documents/upload');
        }
    }

    /**
     * View document
     */
    public function view($id)
    {
        Auth::require();

        $document = $this->documentModel->find($id);
        if (!$document) {
            Utils::flash('error', 'Doküman bulunamadı');
            redirect('/documents');
        }

        // Check if user has access
        if (!$this->hasAccess($document)) {
            Utils::flash('error', 'Bu dokümana erişim yetkiniz yok');
            redirect('/documents');
        }

        if (!file_exists($document['file_path'])) {
            Utils::flash('error', 'Dosya bulunamadı');
            redirect('/documents');
        }

        // Log view activity
        ActivityLogger::log('document.viewed', 'building_document', $id);

        // Serve file
        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: inline; filename="' . $document['file_name'] . '"');
        header('Content-Length: ' . $document['file_size']);
        readfile($document['file_path']);
        exit;
    }

    /**
     * Download document
     */
    public function download($id)
    {
        Auth::require();

        $document = $this->documentModel->find($id);
        if (!$document) {
            Utils::flash('error', 'Doküman bulunamadı');
            redirect('/documents');
        }

        if (!$this->hasAccess($document)) {
            Utils::flash('error', 'Bu dokümana erişim yetkiniz yok');
            redirect('/documents');
        }

        if (!file_exists($document['file_path'])) {
            Utils::flash('error', 'Dosya bulunamadı');
            redirect('/documents');
        }

        // Log download activity
        ActivityLogger::log('document.downloaded', 'building_document', $id);

        // Serve file for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $document['file_name'] . '"');
        header('Content-Length: ' . $document['file_size']);
        readfile($document['file_path']);
        exit;
    }

    /**
     * Delete document
     */
    public function delete($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/documents'));
        }

        try {
            $document = $this->documentModel->find($id);
            if (!$document) {
                throw new Exception('Doküman bulunamadı');
            }

            // Check if user has permission to delete
            if ($document['uploaded_by'] !== Auth::id() && !Auth::hasRole('admin')) {
                throw new Exception('Bu dokümanı silme yetkiniz yok');
            }

            $this->documentModel->delete($id);

            ActivityLogger::log('document.deleted', 'building_document', $id, [
                'title' => $document['title']
            ]);

            Utils::flash('success', 'Doküman başarıyla silindi');
            redirect('/documents?building_id=' . $document['building_id']);

        } catch (Exception $e) {
            Utils::flash('error', 'Silme hatası: ' . Utils::safeExceptionMessage($e));
            redirect('/documents');
        }
    }

    /**
     * Update document
     */
    public function update($id)
    {
        Auth::require();
        if (!CSRF::verifyRequest()) {
            Utils::flash('error', 'Güvenlik doğrulaması başarısız (CSRF)');
            redirect(base_url('/documents'));
        }

        try {
            $document = $this->documentModel->find($id);
            if (!$document) {
                throw new Exception('Doküman bulunamadı');
            }

            if ($document['uploaded_by'] !== Auth::id() && !Auth::hasRole('admin')) {
                throw new Exception('Bu dokümanı düzenleme yetkiniz yok');
            }

            $data = [
                'title' => $_POST['title'] ?? $document['title'],
                'description' => $_POST['description'] ?? $document['description'],
                'document_type' => $_POST['document_type'] ?? $document['document_type'],
                'is_public' => isset($_POST['is_public'])
            ];

            $this->documentModel->update($id, $data);

            ActivityLogger::log('document.updated', 'building_document', $id, [
                'title' => $data['title']
            ]);

            Utils::flash('success', 'Doküman başarıyla güncellendi');
            redirect('/documents?building_id=' . $document['building_id']);

        } catch (Exception $e) {
            Utils::flash('error', 'Güncelleme hatası: ' . Utils::safeExceptionMessage($e));
            redirect('/documents');
        }
    }

    /**
     * Check if user has access to document
     */
    private function hasAccess($document)
    {
        // Admin can access all documents
        if (Auth::hasRole('admin')) {
            return true;
        }

        // Public documents are accessible to all authenticated users
        if ($document['is_public']) {
            return true;
        }

        // User can access documents they uploaded
        if ($document['uploaded_by'] === Auth::id()) {
            return true;
        }

        // Resident can access documents for their unit
        if (isset($_SESSION['resident_unit_id']) && $document['unit_id'] == $_SESSION['resident_unit_id']) {
            return true;
        }

        return false;
    }
}