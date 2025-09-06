<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Modern Furniture Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
  :root {
    --primary: #3498db;
    --secondary: #2980b9;
    --accent: #2c3e50;
    --light: #f8f9fa;
    --dark: #343a40;
  }
  body {
    font-family: 'Segoe UI', sans-serif;
    background-color: #f5f8fa;
  }
  .app-header {
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 15px 20px;
  }
  .sidebar {
    background: white;
    min-height: 100vh;
    box-shadow: 2px 0 10px rgba(0,0,0,0.05);
    position: fixed;
    width: 16.666667%;
  }
  .sidebar-item {
    color: var(--dark);
    border-left: 3px solid transparent;
    transition: all 0.3s;
  }
  .sidebar-item:hover, .sidebar-item.active {
    background: #e9f5ff;
    border-left: 3px solid var(--primary);
    color: var(--primary);
  }
  .main-content {
    background: white;
    min-height: 100vh;
    margin-left: 16.666667%;
    width: 83.333333%;
  }
  a.d-block.py-2.px-3.mb-2.sidebar-item {
    font-size: smaller;
  }
  .stat-card {
    transition: transform 0.3s, box-shadow 0.3s;
  }
  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
  }
  .text-rupee {
    font-family: Arial, sans-serif;
    font-weight: bold;
  }
</style>