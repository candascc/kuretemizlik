<?php
/**
 * Test Building Module
 */

require_once __DIR__ . '/../../config/config.php';

echo "=== Building Management Module Test ===\n\n";

$db = Database::getInstance();

// Test 1: Tables exist
echo "1. Checking tables...\n";
$tables = ['buildings', 'units', 'management_fees', 'building_expenses', 'building_documents'];
$allExist = true;

foreach ($tables as $table) {
    try {
        $exists = $db->fetch("SELECT name FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
        if ($exists) {
            echo "  ✓ $table\n";
        } else {
            echo "  ✗ $table (NOT FOUND)\n";
            $allExist = false;
        }
    } catch (Exception $e) {
        echo "  ✗ $table (ERROR: " . $e->getMessage() . ")\n";
        $allExist = false;
    }
}

if (!$allExist) {
    echo "\n⚠ Some tables are missing. Running migrations...\n";
    require_once __DIR__ . '/RunBuildingMigrations.php';
}

echo "\n";

// Test 2: Models can be instantiated
echo "2. Testing models...\n";
try {
    $buildingModel = new Building();
    echo "  ✓ Building model\n";
    
    $unitModel = new Unit();
    echo "  ✓ Unit model\n";
    
    $feeModel = new ManagementFee();
    echo "  ✓ ManagementFee model\n";
    
    $expenseModel = new BuildingExpense();
    echo "  ✓ BuildingExpense model\n";
} catch (Exception $e) {
    echo "  ✗ Model error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Controllers can be instantiated
echo "3. Testing controllers...\n";
try {
    $buildingController = new BuildingController();
    echo "  ✓ BuildingController\n";
    
    $unitController = new UnitController();
    echo "  ✓ UnitController\n";
    
    $feeController = new ManagementFeeController();
    echo "  ✓ ManagementFeeController\n";
} catch (Exception $e) {
    echo "  ✗ Controller error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 4: Try creating a test building
echo "4. Testing CRUD operations...\n";
try {
    $buildingModel = new Building();
    
    // Check if test building exists
    $testBuilding = $db->fetch("SELECT * FROM buildings WHERE name = 'Test Bina'");
    
    if (!$testBuilding) {
        $id = $buildingModel->create([
            'name' => 'Test Bina',
            'building_type' => 'apartman',
            'address_line' => 'Test Mahallesi, Test Sokak No:1',
            'city' => 'İstanbul',
            'total_units' => 10,
            'status' => 'active'
        ]);
        echo "  ✓ Created test building (ID: $id)\n";
        
        // Update test
        $buildingModel->update($id, ['total_units' => 12]);
        echo "  ✓ Updated test building\n";
        
        // Find test
        $found = $buildingModel->find($id);
        if ($found) {
            echo "  ✓ Found test building\n";
        }
        
        // Delete test
        $buildingModel->delete($id);
        echo "  ✓ Deleted test building\n";
    } else {
        echo "  ℹ Test building already exists (ID: {$testBuilding['id']})\n";
    }
} catch (Exception $e) {
    echo "  ✗ CRUD error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";

