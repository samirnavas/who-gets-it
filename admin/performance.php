<?php
/**
 * Admin Performance Monitoring Interface
 * Provides database performance statistics and optimization tools
 */

session_start();
require_once '../includes/auth_helper.php';
require_once '../includes/performance_helper.php';
require_once '../includes/csrf_helper.php';
require_once '../includes/header.php';

// Require admin privileges
requireAdmin();

// Handle optimization requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Validate CSRF token
    if (!checkCSRFToken()) {
        $error_message = 'Security validation failed';
    } else {
        if ($_POST['action'] === 'optimize_tables') {
            $result = optimizeDatabaseTables();
            if ($result['success']) {
                $success_message = 'Database tables optimized successfully';
            } else {
                $error_message = 'Error optimizing tables: ' . $result['error'];
            }
        }
    }
}

// Get performance statistics
$performance_stats = getDatabasePerformanceStats();
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Performance Monitoring</h1>
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success" role="alert">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Database Optimization Tools -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Database Optimization Tools</h5>
                </div>
                <div class="card-body">
                    <p class="card-text">
                        Optimize database tables to improve query performance and reclaim unused space.
                    </p>
                    <form method="POST" class="form-responsive" style="display: inline;">
                        <?= getCSRFTokenField() ?>
                        <input type="hidden" name="action" value="optimize_tables">
                        <button type="submit" class="form-button-secondary border-orange-300 text-orange-700 hover:bg-orange-50" 
                                onclick="return confirm('This may take a few minutes. Continue?')">
                            Optimize Database Tables
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Table Statistics -->
            <?php if (!empty($performance_stats['table_stats'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Database Table Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Table Name</th>
                                        <th>Rows</th>
                                        <th>Size (MB)</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performance_stats['table_stats'] as $table): ?>
                                        <tr>
                                            <td><strong><?= htmlspecialchars($table['table_name']) ?></strong></td>
                                            <td><?= number_format($table['table_rows']) ?></td>
                                            <td><?= number_format($table['size_mb'], 2) ?></td>
                                            <td>
                                                <?php if ($table['size_mb'] > 100): ?>
                                                    <span class="badge bg-warning">Large</span>
                                                <?php elseif ($table['table_rows'] > 10000): ?>
                                                    <span class="badge bg-info">High Volume</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Normal</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Index Usage Statistics -->
            <?php if (!empty($performance_stats['index_stats'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Index Usage Statistics</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            Shows how frequently database indexes are being used. Low usage may indicate unnecessary indexes.
                        </p>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Table</th>
                                        <th>Index Name</th>
                                        <th>Read Count</th>
                                        <th>Write Count</th>
                                        <th>Usage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($performance_stats['index_stats'] as $index): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($index['object_name']) ?></td>
                                            <td><?= htmlspecialchars($index['index_name']) ?></td>
                                            <td><?= number_format($index['count_read']) ?></td>
                                            <td><?= number_format($index['count_write']) ?></td>
                                            <td>
                                                <?php if ($index['count_read'] > 1000): ?>
                                                    <span class="badge bg-success">High</span>
                                                <?php elseif ($index['count_read'] > 100): ?>
                                                    <span class="badge bg-info">Medium</span>
                                                <?php elseif ($index['count_read'] > 0): ?>
                                                    <span class="badge bg-warning">Low</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Unused</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Performance Recommendations -->
            <div class="card">
                <div class="card-header">
                    <h5>Performance Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Database Maintenance</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check-circle text-success"></i> Run table optimization monthly</li>
                                <li><i class="fas fa-check-circle text-success"></i> Monitor table sizes regularly</li>
                                <li><i class="fas fa-check-circle text-success"></i> Archive old auction data</li>
                                <li><i class="fas fa-check-circle text-success"></i> Review unused indexes</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Query Optimization</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-info-circle text-info"></i> Use pagination for large result sets</li>
                                <li><i class="fas fa-info-circle text-info"></i> Limit search result counts</li>
                                <li><i class="fas fa-info-circle text-info"></i> Cache frequently accessed data</li>
                                <li><i class="fas fa-info-circle text-info"></i> Monitor slow query logs</li>
                            </ul>
                        </div>
                    </div>
                    
                    <?php
                    // Calculate total database size
                    $total_size = 0;
                    if (!empty($performance_stats['table_stats'])) {
                        foreach ($performance_stats['table_stats'] as $table) {
                            $total_size += $table['size_mb'];
                        }
                    }
                    ?>
                    
                    <?php if ($total_size > 0): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <strong>Database Summary:</strong>
                            Total size: <?= number_format($total_size, 2) ?> MB
                            
                            <?php if ($total_size > 500): ?>
                                <span class="badge bg-warning ms-2">Large Database</span>
                                <br><small class="text-muted">
                                    Consider implementing data archiving strategies for better performance.
                                </small>
                            <?php elseif ($total_size > 100): ?>
                                <span class="badge bg-info ms-2">Medium Database</span>
                                <br><small class="text-muted">
                                    Monitor growth and optimize regularly.
                                </small>
                            <?php else: ?>
                                <span class="badge bg-success ms-2">Optimal Size</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>