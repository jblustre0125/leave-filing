<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php?page=login');
    exit;
}
//fetch employee name from the database
require_once __DIR__ . '/../../config/db-handler.php';
$leaveTypes = selectDataLeave('LeaveType', 'LeaveTypeId, LeaveTypeName', 'IsActive = 1');
$employee = selectDataLeave('Employee', 'EmployeeName, EmployeeCode, DepartmentId, PositionId, DateHired, TeamId, EmploymentTypeId', 'EmployeeId = ?', [$_SESSION['user_id']]);
$employeeName = $employee && count($employee) === 1 ? $employee[0]['EmployeeName'] : $_SESSION['username'];
$employeeCode = $employee[0]['EmployeeCode'] ?? '';
$departmentId = $employee[0]['DepartmentId'] ?? '';
$positionId = $employee[0]['PositionId'] ?? '';
$dateHired = $employee[0]['DateHired'] ?? '';
$empStatus = $employee[0]['EmploymentTypeId'] ?? '';
$teamId = $employee[0]['TeamId'] ?? '';

$departmentName = '';
$positionName = '';
$teamName = '';
$employmentTypeName = '';

if ($departmentId) {
    $dept = selectDataLeave('Department', 'DepartmentName', 'DepartmentId = ?', [$departmentId]);
    $departmentName = $dept[0]['DepartmentName'] ?? '';
}

if ($positionId) {
    $pos = selectDataLeave('Position', 'PositionName', 'PositionId = ?', [$positionId]);
    $positionName = $pos[0]['PositionName'] ?? '';
}
if ($teamId) {
    $team = selectDataLeave('Team', 'TeamName', 'TeamId = ?', [$teamId]);
    $teamName = $team[0]['TeamName'] ?? '';
}

if ($empStatus) {
    $etype = selectDataLeave('EmploymentType', 'EmploymentTypeName', 'EmploymentTypeId = ?', [$empStatus]);
    $employmentTypeName = $etype[0]['EmploymentTypeName'] ?? '';
}

//pagination setup
$page = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$limit = 10;  //records per page
$offset = ($page - 1) * $limit;

//count total records
$totalRowsArr = selectDataLeave('LeaveFiling', 'COUNT(*) AS total', 'EmployeeId = ?', [$_SESSION['user_id']]);
$totalRows = $totalRowsArr[0]['total'];
$totalPages = ceil($totalRows / $limit);

//fetch paginated leave applications
$leaves = [];
$sql = "SELECT LF.DateCreated, LT.LeaveTypeName, LF.StartDate, LF.EndDate, LF.Quantity, LF.Reason, RS.RoutingStatusName, LF.LeaveFileId,
        LF.SuperiorId1, LF.SuperiorId2, LF.ManagerId,
        S.EmployeeName AS SupervisorName,
        M.EmployeeName AS ManagerName,
        P.EmployeeName AS PlantManagerName
        FROM LeaveFiling LF
        INNER JOIN LeaveType LT ON LF.LeaveTypeId = LT.LeaveTypeId
        INNER JOIN RoutingStatus RS ON LF.RoutingStatusId = RS.RoutingStatusId
        LEFT JOIN Employee S ON LF.SuperiorId1 = S.EmployeeId
        LEFT JOIN Employee M ON LF.SuperiorId2 = M.EmployeeId
        LEFT JOIN Employee P ON LF.ManagerId = P.EmployeeId
        WHERE LF.EmployeeId = ?
        ORDER BY LF.DateCreated DESC
        OFFSET $offset ROWS
        FETCH NEXT $limit ROWS ONLY";
$stmt = $connLeave->prepare($sql);
$stmt->execute([$_SESSION['user_id']]);
$leaves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container">
    <div class="row mt-4 mb-3">
        <div class="col-md-8">
            <h2 class="fw-bold">Welcome, <?php echo htmlspecialchars($employeeName); ?></h2>
            <p class="text-muted">Here you can manage your leave application and view your leave history.</p>
        </div>
        <div class="col-md-4 d-flex justify-content-end align-items-center gap-2">
            <span class="badge bg-primary fs-6"><?php echo date('l, d M Y'); ?></span>
            <form action="/leave-filing/logout.php" method="POST" style="display:inline;">
                <button type="submit" class="btn btn-danger btn-sm" title="Logout"> <i class="bi bi-box-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <div class="card-shadow-sm mb-4">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">My Leave Application</span>
                <a href="#" class="btn btn-success btn-sm"
                    data-bs-toggle="modal"
                    data-bs-target="#applyLeaveModal">Apply for Leave</a>
            </div>
        </div>

        <div class="card-body p-0 mt-3">
            <div class=" table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr class="text-center">
                            <th style="width: 115px;">Date Created</th>
                            <th style="width: 130px;">Leave Type</th>
                            <th style="width: 180px;">Name</th>
                            <th style="width: 110px;">From</th>
                            <th style="width: 110px;">To</th>
                            <th style="width: 60px;">Qty</th>
                            <th style="width: 250px;">Reason</th>
                            <th style="width: 180px;">Status</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leaves): ?>
                            <?php foreach ($leaves as $leave): ?>
                                <tr class="text-center">
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['DateCreated']))); ?></td>
                                    <td><?php echo htmlspecialchars($leave['LeaveTypeName']); ?></td>
                                    <td><?php echo htmlspecialchars($employeeName); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['StartDate']))); ?></td>
                                    <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['EndDate']))); ?></td>
                                    <td><?php echo intval($leave['Quantity']); ?></td>
                                    <td><?php echo htmlspecialchars($leave['Reason']); ?></td>
                                    <td><?php echo htmlspecialchars($leave['RoutingStatusName']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary view-btn" data-bs-toggle="modal"
                                            data-bs-target="#viewLeaveModal"
                                            data-datecreated="<?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['DateCreated']))); ?>"
                                            data-leavetype="<?php echo htmlspecialchars($leave['LeaveTypeName']); ?>"
                                            data-name="<?php echo htmlspecialchars($employeeName); ?>"
                                            data-startdate="<?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['StartDate']))); ?>"
                                            data-enddate="<?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['EndDate']))); ?>"
                                            data-quantity="<?php echo intval($leave['Quantity']); ?>"
                                            data-reason="<?php echo htmlspecialchars($leave['Reason']); ?>"
                                            data-status="<?php echo htmlspecialchars($leave['RoutingStatusName']); ?>"
                                            data-assistsupervisor="<?php echo htmlspecialchars($leave['SupervisorName'] ?? 'N/A'); ?>"
                                            data-supervisormanager="<?php echo htmlspecialchars($leave['ManagerName'] ?? 'N/A'); ?>"
                                            data-finalapprover="<?php echo htmlspecialchars($leave['PlantManagerName'] ?? 'N/A'); ?>"
                                            <?php if (strtolower($leave['RoutingStatusName']) === 'approved') echo 'disabled'; ?>>View
                                        </button>
                                        <button class="btn btn-sm btn-warning edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editLeaveModal"
                                            data-leavefileid="<?php echo $leave['LeaveFileId']; ?>"
                                            data-datecreated="<?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['DateCreated']))); ?>"
                                            data-leavetype="<?php echo htmlspecialchars($leave['LeaveTypeName']); ?>"
                                            data-name="<?php echo htmlspecialchars($employeeName); ?>"
                                            data-startdate="<?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['StartDate']))); ?>"
                                            data-enddate="<?php echo htmlspecialchars(date('Y-m-d', strtotime($leave['EndDate']))); ?>"
                                            data-quantity="<?php echo intval($leave['Quantity']); ?>"
                                            data-reason="<?php echo htmlspecialchars($leave['Reason']); ?>"
                                            data-status="<?php echo htmlspecialchars($leave['RoutingStatusName']); ?>"
                                            data-assistsupervisor="<?php echo $leave['SuperiorId1'] ?? ''; ?>"
                                            data-supervisormanager="<?php echo $leave['SuperiorId2'] ?? ''; ?>"
                                            data-finalapprover="<?php echo $leave['ManagerId'] ?? ''; ?>"
                                            <?php if (strtolower($leave['RoutingStatusName']) === 'approved') echo 'disabled'; ?>>Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No leave applications found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Modal for apply leave -->
        <div class="modal fade" id="applyLeaveModal" tabindex="-1" aria-labelledby="applyLeaveModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <form action="../handlers/apply-leave.php" method="post" id="applyLeaveForm">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title text-primary fw-bold" id="applyLeaveLabel">Apply for Leave</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-white">
                            <!-- Employee Information -->
                            <div class="rounded-3 p-3 mb-3" style="background:#f4f8ff; border:1px solid #e3eafc;">
                                <div class="row g-2 mb-1">
                                    <div class="col-md-3">
                                        <label class="form-label text-primary fw-semibold mb-0">Name</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($employeeName); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold mb-0">ID Number</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($employeeCode); ?>" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-primary fw-semibold mb-0">Department</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($departmentName) . ' - ' . htmlspecialchars($teamName); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold mb-0">Position</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($positionName); ?>" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold mb-0">Date Hired</label>
                                        <input type="text" class="form-control bg-light"
                                            value="<?php echo $dateHired ? date('F d, Y', strtotime($dateHired)) : ''; ?>" readonly>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold mb-0">Emp. Status</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($employmentTypeName); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <!-- Leave Details -->
                            <div class="rounded-3 p-3 mb-3" style="background:#f4f8ff; border:1px solid #e3eafc;">
                                <div class="row g-2 align-items-end mb-2">
                                    <div class="col-md-3">
                                        <label class="form-label text-primary fw-semibold">Leave Type</label>
                                        <select name="LeaveTypeId" class="form-select border-primary" required>
                                            <option value="">Select Leave Type</option>
                                            <?php foreach ($leaveTypes as $type): ?>
                                                <option value="<?php echo $type['LeaveTypeId']; ?>"><?php echo htmlspecialchars($type['LeaveTypeName']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold">Leave Credits</label>
                                        <input type="text" id="leaveCreditsInput" class="form-control text-center bg-light border-primary" value="0" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold">Leave Balance</label>
                                        <input type="text" class="form-control text-center bg-light border-primary" value="0" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold">Start Date</label>
                                        <input type="date" name="StartDate" class="form-control border-primary" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold">End Date</label>
                                        <input type="date" name="EndDate" class="form-control border-primary" required>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label text-primary fw-semibold">No. of Day(s)</label>
                                        <input type="number" name="Quantity" class="form-control text-center bg-light border-primary" min="1" value="1" readonly>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2">
                                    <div class="col-md-12">
                                        <label class="form-label text-primary fw-semibold">Reason</label>
                                        <textarea name="Reason" class="form-control border-primary" rows="3" required style="min-height:60px;"></textarea>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $currentDepartmentId = $employee[0]['DepartmentId'] ?? null;
                            $currentTeamId = $employee[0]['TeamId'] ?? null;

                            $asstSupervisors = selectDataLeave(
                                'Employee',
                                'EmployeeId, EmployeeName',
                                'PositionId IN (?, ?) AND IsApprover = 1',
                                [4, 19]
                            );
                            $supervisors = selectDataLeave(
                                'Employee',
                                'EmployeeId, EmployeeName',
                                'PositionId IN (?, ?) AND IsApprover = 1',
                                [19, 2]
                            );
                            $finalApprovers = selectDataLeave(
                                'Employee',
                                'EmployeeId, EmployeeName',
                                'PositionId IN (?, ?, ?) AND IsApprover = 1',
                                [2, 15, 43]
                            );
                            ?>
                            <!-- Approvers Section -->
                            <div class="mb-3">
                                <div class="row g-2">
                                    <!-- Asst. Supervisor/Supervisor -->
                                    <div class="col-md-4">
                                        <div class="rounded-3 p-2 border border-primary bg-white">
                                            <div class="fw-semibold text-primary mb-2">Asst. Supervisor/Supervisor</div>
                                            <label class="form-label mb-1">Status</label>
                                            <select class="form-select mb-1 border-primary" name="SuperiorStatus1" disabled>
                                                <option value="">Select Status</option>
                                            </select>
                                            <label class="form-label mb-1">Name</label>
                                            <select class="form-select mb-1 border-primary" name="SuperiorId1" required>
                                                <option value="">Select Approver</option>
                                                <?php foreach ($asstSupervisors as $emp): ?>
                                                    <option value="<?php echo $emp['EmployeeId']; ?>"><?php echo htmlspecialchars($emp['EmployeeName']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="form-label mb-1">Position</label>
                                            <input type="text" class="form-control mb-1 bg-light" name="SuperiorPosition1" readonly>
                                            <label class="form-label mb-1">Date</label>
                                            <input type="date" class="form-control mb-1 bg-light" name="SuperiorDate1" readonly>
                                            <label class="form-label mb-1">Remarks</label>
                                            <textarea class="form-control mb-1 bg-light" name="SuperiorRemarks1" readonly style="min-height:40px;"></textarea>
                                        </div>
                                    </div>
                                    <!-- Supervisor/Manager -->
                                    <div class="col-md-4">
                                        <div class="rounded-3 p-2 border border-primary bg-white">
                                            <div class="fw-semibold text-primary mb-2">Supervisor/Manager</div>
                                            <label class="form-label mb-1">Status</label>
                                            <select class="form-select mb-1 border-primary" name="SuperiorStatus2" disabled>
                                                <option value="">Select Status</option>
                                            </select>
                                            <label class="form-label mb-1">Name</label>
                                            <select class="form-select mb-1 border-primary" name="SuperiorId2" required>
                                                <option value="">Select Approver</option>
                                                <?php foreach ($supervisors as $emp): ?>
                                                    <option value="<?php echo $emp['EmployeeId']; ?>"><?php echo htmlspecialchars($emp['EmployeeName']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="form-label mb-1">Position</label>
                                            <input type="text" class="form-control mb-1 bg-light" name="SuperiorPosition2" readonly>
                                            <label class="form-label mb-1">Date</label>
                                            <input type="date" class="form-control mb-1 bg-light" name="SuperiorDate2" readonly>
                                            <label class="form-label mb-1">Remarks</label>
                                            <textarea class="form-control mb-1 bg-light" name="SuperiorRemarks2" readonly style="min-height:40px;"></textarea>
                                        </div>
                                    </div>
                                    <!-- Manager/Final Approver -->
                                    <div class="col-md-4">
                                        <div class="rounded-3 p-2 border border-primary bg-white">
                                            <div class="fw-semibold text-primary mb-2">Manager/Final Approver</div>
                                            <label class="form-label mb-1">Status</label>
                                            <select class="form-select mb-1 border-primary" name="ManagerStatus" disabled>
                                                <option value="">Select Status</option>
                                            </select>
                                            <label class="form-label mb-1">Name</label>
                                            <select class="form-select mb-1 border-primary" name="ManagerId" required>
                                                <option value="">Select Approver</option>
                                                <?php foreach ($finalApprovers as $emp): ?>
                                                    <option value="<?php echo $emp['EmployeeId']; ?>"><?php echo htmlspecialchars($emp['EmployeeName']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label class="form-label mb-1">Position</label>
                                            <input type="text" class="form-control mb-1 bg-light" name="ManagerPosition" readonly>
                                            <label class="form-label mb-1">Date</label>
                                            <input type="date" class="form-control mb-1 bg-light" name="ManagerDate" readonly>
                                            <label class="form-label mb-1">Remarks</label>
                                            <textarea class="form-control mb-1 bg-light" name="ManagerRemarks" readonly style="min-height:40px;"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clinic Section -->
                            <div class="rounded-3 p-2 border border-primary bg-white mb-2">
                                <div class="fw-semibold text-primary mb-2">Clinic</div>
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Status</label>
                                        <input type="text" class="form-control bg-light" name="ClinicStatus" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Name</label>
                                        <input type="text" class="form-control bg-light" name="ClinicName" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Position</label>
                                        <input type="text" class="form-control bg-light" name="ClinicPosition" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label mb-1">Date</label>
                                        <input type="date" class="form-control bg-light" name="ClinicDate" readonly>
                                    </div>
                                </div>
                                <div class="row g-2 mt-1">
                                    <div class="col-md-12">
                                        <label class="form-label mb-1">Remarks</label>
                                        <textarea class="form-control bg-light clinic-remarks" name="ClinicRemarks" readonly style="min-height:40px;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="submit" class="btn btn-primary">Submit</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal for view button -->
        <div class="modal fade" id="viewLeaveModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light">
                        <h5 class="modal-title text-primary fw-bold" id="viewModalLabel">Leave Application Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body bg-white">
                        <!-- Employee Information -->
                        <div class="rounded-3 p-3 mb-3" style="background:#f4f8ff; border:1px solid #e3eafc;">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label text-primary fw-semibold mb-0">Name</label>
                                    <input type="text" class="form-control bg-light" value="" id="modal-name" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-primary fw-semibold mb-0">Date Created</label>
                                    <input type="text" class="form-control bg-light" value="" id="modal-datecreated" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-primary fw-semibold mb-0">Leave Type</label>
                                    <input type="text" class="form-control bg-light" value="" id="modal-leavetype" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-primary fw-semibold mb-0">From</label>
                                    <input type="text" class="form-control bg-light" value="" id="modal-from" readonly>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-primary fw-semibold mb-0">To</label>
                                    <input type="text" class="form-control bg-light" value="" id="modal-to" readonly>
                                </div>
                            </div>
                        </div>
                        <!-- Leave Details -->
                        <div class="input-group-view mb-3">
                            <div class="row g-2">
                                <div class="col-md-2">
                                    <label class="form-label">Qty</label>
                                    <input type="text" class="form-control text-center" id="modal-qty" readonly>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" id="modal-status" readonly>
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-12">
                                    <label class="form-label">Reason</label>
                                    <textarea class="form-control" id="modal-reason" readonly></textarea>
                                </div>
                            </div>
                        </div>
                        <!-- Approvers Section -->
                        <div class="mb-3">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <div class="rounded-3 p-2 border border-primary bg-white">
                                        <div class="fw-semibold text-primary mb-2">Asst. Supervisor/Supervisor</div>
                                        <input type="text" class="form-control mb-1 bg-light" id="modal-assistsupervisor" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="rounded-3 p-2 border border-primary bg-white">
                                        <div class="fw-semibold text-primary mb-2">Supervisor/Manager</div>
                                        <input type="text" class="form-control mb-1 bg-light" id="modal-supervisormanager" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="rounded-3 p-2 border border-primary bg-white">
                                        <div class="fw-semibold text-primary mb-2">Manager/Final Approver</div>
                                        <input type="text" class="form-control mb-1 bg-light" id="modal-finalapprover" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for edit button -->
        <div class="modal fade" id="editLeaveModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <form id="editLeaveForm" method="post" action="edit-leave.php">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title text-primary fw-bold" id="editModalLabel">Edit Leave Application</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body bg-white">
                            <input type="hidden" name="LeaveFileId" id="edit-leavefileid">
                            <!-- Employee Information -->
                            <div class="rounded-3 p-3 mb-3" style="background:#f4f8ff; border:1px solid #e3eafc;">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label text-primary fw-semibold mb-0">Name</label>
                                        <input type="text" class="form-control bg-light" name="Name" id="edit-name" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold mb-0">Date Created</label>
                                        <input type="text" class="form-control bg-light" name="DateCreated" id="edit-datecreated" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label text-primary fw-semibold mb-0">Leave Type</label>
                                        <input type="text" class="form-control bg-light" name="LeaveTypeName" id="edit-leavetype" readonly>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold mb-0">From</label>
                                        <input type="date" class="form-control" name="StartDate" id="edit-startdate">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold mb-0">To</label>
                                        <input type="date" class="form-control" name="EndDate" id="edit-enddate">
                                    </div>
                                </div>
                            </div>
                            <!-- Leave Details -->
                            <div class="rounded-3 p-3 mb-3" style="background:#f4f8ff; border:1px solid #e3eafc;">
                                <div class="row g-2">
                                    <div class="col-md-2">
                                        <label class="form-label text-primary fw-semibold">Qty</label>
                                        <input type="number" class="form-control text-center" name="Quantity" id="edit-quantity">
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-12">
                                        <label class="form-label text-primary fw-semibold">Status</label>
                                        <input type="text" class="form-control" name="Status" id="edit-status" readonly>
                                    </div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-12">
                                        <label class="form-label text-primary fw-semibold">Reason</label>
                                        <textarea class="form-control" name="Reason" id="edit-reason"></textarea>
                                    </div>
                                </div>
                            </div>
                            <!-- Approvers Section -->
                            <div class="row g-2 mb-2">
                                <div class="col-md-4">
                                    <div class="rounded-3 p-2 border border-primary bg-white">
                                        <label class="form-label text-primary fw-semibold">Asst. Supervisor/Supervisor</label>
                                        <select class="form-select mb-1 border-primary" name="SuperiorId1" id="edit-assistsupervisor" required>
                                            <option value="">N/A</option>
                                            <?php foreach ($asstSupervisors as $emp): ?>
                                                <option value="<?php echo $emp['EmployeeId']; ?>"><?php echo htmlspecialchars($emp['EmployeeName']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="rounded-3 p-2 border border-primary bg-white">
                                        <label class="form-label text-primary fw-semibold">Supervisor/Manager</label>
                                        <select class="form-select mb-1 border-primary" name="SuperiorId2" id="edit-supervisormanager" required>
                                            <option value="">N/A</option>
                                            <?php foreach ($supervisors as $emp): ?>
                                                <option value="<?php echo $emp['EmployeeId']; ?>"><?php echo htmlspecialchars($emp['EmployeeName']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="rounded-3 p-2 border border-primary bg-white">
                                        <label class="form-label text-primary fw-semibold">Final Approver/Manager</label>
                                        <select class="form-select mb-1 border-primary" name="ManagerId" id="edit-finalapprover" required>
                                            <option value="">N/A</option>
                                            <?php foreach ($finalApprovers   as $emp): ?>
                                                <option value="<?php echo $emp['EmployeeId']; ?>"><?php echo htmlspecialchars($emp['EmployeeName']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="submit" class="btn btn-success">Save Changes</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- script to populate modal data -->
        <script>
            //Auto calculate quantity based on date range for leave modal
            document.addEventListener('DOMContentLoaded', function() {

                //fetch leave Credits
                const leaveTypeSelect = document.querySelector('select[name="LeaveTypeId"]');
                const creditsInput = document.getElementById('leaveCreditsInput');
                leaveTypeSelect.addEventListener('change', function() {
                    const leaveTypeId = this.value;
                    if (!leaveTypeId) {
                        creditsInput.value = 0;
                        return;
                    }
                    fetch('../handlers/ajax/ajax-leave-credits.php?LeaveTypeId=' + leaveTypeId)
                        .then(response => response.json())
                        .then(data => {
                            creditsInput.value = data.credits;
                        })
                        .catch(() => {
                            creditsInput.value = 0;
                        });
                });

                const startDateInput = document.getElementById('floatingStartDate');
                const endDateInput = document.getElementById('floatingEndDate');
                const quantityInput = document.getElementById('floatingQuantity');

                function calculateQuantity() {
                    const startDate = new Date(startDateInput.value);
                    const endDate = new Date(endDateInput.value);
                    if (startDate && endDate && startDate <= endDate) {
                        const timeDiff = endDate - startDate;
                        const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1;
                        quantityInput.value = daysDiff;
                    } else {
                        quantityInput.value = 1;
                    }
                }

                if (startDateInput && endDateInput) {
                    startDateInput.addEventListener('change', calculateQuantity);
                    endDateInput.addEventListener('change', calculateQuantity);
                }
            });
        </script>
        <script>
            // view modal
            viewLeaveModal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                document.getElementById('modal-datecreated').value = button.getAttribute('data-datecreated');
                document.getElementById('modal-leavetype').value = button.getAttribute('data-leavetype');
                document.getElementById('modal-name').value = button.getAttribute('data-name');
                document.getElementById('modal-from').value = button.getAttribute('data-startdate');
                document.getElementById('modal-to').value = button.getAttribute('data-enddate');
                document.getElementById('modal-qty').value = button.getAttribute('data-quantity');
                document.getElementById('modal-reason').value = button.getAttribute('data-reason');
                document.getElementById('modal-status').value = button.getAttribute('data-status');
                document.getElementById('modal-assistsupervisor').value = button.getAttribute('data-assistsupervisor');
                document.getElementById('modal-supervisormanager').value = button.getAttribute('data-supervisormanager');
                document.getElementById('modal-finalapprover').value = button.getAttribute('data-finalapprover');
            });

            // edit modal
            const disabledStatuses = [
                'scheduled on effectivity date',
                'approved by manager/final approver',
                'disapproved (leave w/o pay)',
                'disapproved (lwop with da)'
            ]
            const editLeaveModal = document.getElementById('editLeaveModal');
            editLeaveModal.addEventListener('show.bs.modal', (event) => {
                const button = event.relatedTarget;
                document.getElementById('edit-leavefileid').value = button.getAttribute('data-leavefileid');
                document.getElementById('edit-datecreated').value = button.getAttribute('data-datecreated');
                document.getElementById('edit-leavetype').value = button.getAttribute('data-leavetype');
                document.getElementById('edit-name').value = button.getAttribute('data-name');
                document.getElementById('edit-startdate').value = button.getAttribute('data-startdate');
                document.getElementById('edit-enddate').value = button.getAttribute('data-enddate');
                document.getElementById('edit-quantity').value = button.getAttribute('data-quantity');
                document.getElementById('edit-reason').value = button.getAttribute('data-reason');
                document.getElementById('edit-status').value = button.getAttribute('data-status');
                document.getElementById('edit-assistsupervisor').value = button.getAttribute('data-assistsupervisor') || '';
                document.getElementById('edit-supervisormanager').value = button.getAttribute('data-supervisormanager') || '';
                document.getElementById('edit-finalapprover').value = button.getAttribute('data-finalapprover') || '';



                //check if status is in the disabled list
                const status = button.getAttribute('data-status').toLowerCase();
                const isDisabled = disabledStatuses.includes(status);

                //Fields to disabled
                const fields = [
                    'edit-startdate',
                    'edit-enddate',
                    'edit-quantity',
                    'edit-reason'
                ];
                fields.forEach(fileId => {
                    document.getElementById(fileId).readOnly = isDisabled;
                    document.getElementById(fileId).classList.toggle('bg-light', isDisabled);
                });

                //disabled save changes button if needed
                document.getElementById('edit-assistsupervisor').disabled = isDisabled;
                document.getElementById('edit-supervisormanager').disabled = isDisabled;
                document.getElementById('edit-finalapprover').disabled = isDisabled;

                document.querySelector('#editLeaveForm button[type="submit"]').disabled = isDisabled;
            });
        </script>
    </div>
</div>