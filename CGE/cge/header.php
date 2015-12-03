<head>
  <meta charset="utf-8">
  <meta name="keywords" content="center, genomic, epidemiology, CGE, CBS, typing, phenotyping, phylogeny, sequencing, genome">
  <meta name="description" content="CGE provides webservices and research on microbiological sequencing">
  <meta name="author" content="Center for Biological Sequence Analysis at the Technical University of Denmark">

  <!-- include needed css library and favicon -->
  <link rel="icon" type="image/png" href="https://cge.cbs.dtu.dk/favicon.ico">
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
  <link rel="stylesheet" href="main.css">
  <!-- include needed javascript library -->
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
  <script src="//maxcdn.bootstrapcdn.com/bootstrap/3.2.0/js/bootstrap.min.js"></script>
  <script src="/cge/user/login/js/login.js"></script>

  <title>Center for Genomic Epidemiology</title>
</head>

<body class="container">
  <header>
    <!-- Title logo -->
    <div class="banner">
      <h1><b>Center for Genomic Epidemiology</b></h1>
    </div>
    <!-- Navigation bar -->
    <div class="bs-example">
      <nav role="navigation" class="navbar navbar-default">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
          <button type="button" data-target="#navbarCollapse" data-toggle="collapse" class="navbar-toggle">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a href="#" class="navbar-brand">Home</a>
        </div>
        <!-- Collection of nav links, forms, and other content for toggling -->
        <div id="navbarCollapse" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li><a href="organization.php">Organization</a></li>
            <li><a href="project.php">Project</a></li>
            <li><a href="../services.php">Services</a></li>
            <li><a href="contact.php">Contact</a></li>
          </ul>
          <ul class="nav navbar-nav navbar-right">
            <li class="dropdown">
            <a class="dropdown-toggle" href="#" data-toggle="dropdown">Sign In <strong class="caret"></strong></a>
            <div id="login-menu" class="dropdown-menu">
              <!-- Login form here -->
              <form action="/cge/user/login/user_manager.php?action=show" method="POST" accept-charset="UTF-8">
                <div class="form-group has-feedback">
                  <label class="control-label">Username</label>
                  <input id="USERNAME" type="text" class="form-control" name="USERNAME" placeholder="Username" />
                  <i class="glyphicon glyphicon-user form-control-feedback"></i>
                </div>
                <div class="form-group has-feedback">
                  <label class="control-label">Password</label>
                  <input id="PASSWORD" type="password" class="form-control" name="PASSWORD" placeholder="Password" />
                  <i class="glyphicon glyphicon-asterisk form-control-feedback"></i>
                </div>
                <input  class="btn btn-primary" type="button" onClick="login(form.USERNAME.value, form.PASSWORD.value);" value="Login 1" />
                <input  class="btn btn-primary" type="submit" value="Login 2" />
                <li>Not registered yet? <a href="/user/sign_up.php">Sign up here!</a></li>
              </form>
            </div>
          </li>
          </ul>
          <form role="search" class="navbar-form navbar-right">
            <div class="form-search">
              <div class="input-append">
                <input type="text" placeholder="Search" class="form-control">
                <button type="submit" class="btn btn-default"><span class="glyphicon glyphicon-search"></span></button>
              </div>
            </div>
          </form>
          <ul>
            <?php   
              if(isset($_SESSION['username']) and $_SESSION['username']!='') {
                echo '<li><a href="logout.php"><span>Log Out</span></a></li>';
              } else {
                echo '<li><a href="login.php"><span>Log In</span></a></li>';
              } 
            ?> 
          </ul>
        </div>
      </nav>
    </div>
  </header>
