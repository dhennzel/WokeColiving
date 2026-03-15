<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Woke Coliving | Admin Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="admin.css">
</head>
<body>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-placeholder">
                    <i class="fas fa-leaf"></i>
                </div>
                <span class="brand-name">Woke Coliving</span>
            </div>
            
            <nav class="sidebar-nav">
                <a href="#" class="nav-item active">
                    <i class="fas fa-chart-pie"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-bed"></i>
                    <span>Rooms</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Reservations</span>
                    <span class="nav-badge">3</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Residents</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="main-wrapper">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="navbar-left">
                    <button id="sidebarToggle" class="icon-btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search anything...">
                    </div>
                </div>
                
                <div class="navbar-right">
                    <button class="icon-btn notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="badge">5</span>
                    </button>
                    
                    <div class="profile-dropdown">
                        <div class="profile-toggle" id="profileToggle">
                            <img src="https://ui-avatars.com/api/?name=Admin+User&background=2DC08F&color=fff" alt="Admin Profile" class="profile-img">
                            <span class="profile-name">Admin User</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="dropdown-menu" id="profileMenu">
                            <a href="#"><i class="fas fa-user"></i> My Profile</a>
                            <a href="#"><i class="fas fa-sign-out-alt"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="main-content">
                <div class="page-header">
                    <h1>Dashboard Overview</h1>
                    <button class="btn btn-primary" id="openModalBtn">
                        <i class="fas fa-plus"></i> Add Room
                    </button>
                </div>

                <!-- Summary Widgets -->
                <div class="summary-grid">
                    <!-- Card 1 -->
                    <div class="summary-card fade-in" style="--animation-order: 1;">
                        <div class="card-info">
                            <span class="card-title">Total Rooms</span>
                            <h2 class="card-value">124</h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-door-open"></i>
                        </div>
                    </div>
                    <!-- Card 2 -->
                    <div class="summary-card fade-in" style="--animation-order: 2;">
                        <div class="card-info">
                            <span class="card-title">Occupied Beds</span>
                            <h2 class="card-value">89%</h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                    </div>
                    <!-- Card 3 -->
                    <div class="summary-card fade-in" style="--animation-order: 3;">
                        <div class="card-info">
                            <span class="card-title">Pending Bookings</span>
                            <h2 class="card-value">12</h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                    <!-- Card 4 -->
                    <div class="summary-card fade-in" style="--animation-order: 4;">
                        <div class="card-info">
                            <span class="card-title">Monthly Revenue</span>
                            <h2 class="card-value">₱45,200</h2>
                        </div>
                        <div class="card-icon">
                            <i class="fas fa-wallet"></i>
                        </div>
                    </div>
                </div>

                <!-- Data Table Section -->
                <div class="data-section fade-in" style="--animation-order: 5;">
                    <div class="section-header">
                        <h2>Recent Reservations</h2>
                        <button class="btn btn-secondary">View All</button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table" id="reservationsTable">
                            <thead>
                                <tr>
                                    <th data-sort="string">Guest Name <i class="fas fa-sort"></i></th>
                                    <th data-sort="string">Room <i class="fas fa-sort"></i></th>
                                    <th data-sort="date">Check-in <i class="fas fa-sort"></i></th>
                                    <th data-sort="string">Status <i class="fas fa-sort"></i></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Maria Santos</td>
                                    <td>Room 204 (4-Bed)</td>
                                    <td>Oct 12, 2023</td>
                                    <td><span class="status-badge status-pending">Pending</span></td>
                                    <td><button class="btn btn-primary btn-sm">Approve</button></td>
                                </tr>
                                <tr>
                                    <td>Juan Dela Cruz</td>
                                    <td>Room 101 (Single)</td>
                                    <td>Oct 10, 2023</td>
                                    <td><span class="status-badge status-approved">Approved</span></td>
                                    <td><button class="btn btn-secondary btn-sm">View</button></td>
                                </tr>
                                <tr>
                                    <td>Anna Reyes</td>
                                    <td>Room 305 (6-Bed)</td>
                                    <td>Oct 15, 2023</td>
                                    <td><span class="status-badge status-pending">Pending</span></td>
                                    <td><button class="btn btn-primary btn-sm">Approve</button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Action Modal -->
    <div class="modal-overlay" id="actionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Room</h2>
                <button class="close-btn" id="closeModalBtn"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="addRoomForm">
                    <div class="form-group">
                        <label>Room Number</label>
                        <input type="text" placeholder="e.g. 101" required>
                    </div>
                    <div class="form-group">
                        <label>Room Type</label>
                        <select required>
                            <option value="">Select type</option>
                            <option value="single">Single</option>
                            <option value="4-bed">4-Bed Dorm</option>
                            <option value="6-bed">6-Bed Dorm</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" id="cancelModalBtn">Cancel</button>
                <button type="button" class="btn btn-primary">Save Room</button>
            </div>
        </div>
    </div>

    <!-- Custom JS -->
    <script src="admin.js"></script>
</body>
</html>