<?php
	session_start();
//var_dump($_SESSION);
	use PHPOnCouch\CouchClient;//php couch DB library as found at https://github.com/dready92/PHP-on-Couch
	//Include libraries
	{
		include "libraries/CouchLink/vendor/autoload.php";//needed to load phponcouch
		require 'libraries/phpMailer/PHPMailerAutoload.php';
	}
	//declare important Vars
	{
		$time = explode(" ",microtime());
		$now = $time[1]+$time[0];
		$couchDsn = "http://127.0.0.1:5984";//coucgh db settings, if neede these must be manually changed as per documentation provided at https://github.com/dready92/PHP-on-Couch
		$signalsDB = "coremonitor_signals";
		$configDB = "coremonitor_config"; 
		$clientsDB = "coremonitor_clients";
		$usersDB = "coremonitor_users";
		$signalsClient = new CouchClient($couchDsn,$signalsDB);
		$configClient = new CouchClient($couchDsn,$configDB);
		$usersClient = new CouchClient($couchDsn,$usersDB);
		$clientsClient = new CouchClient($couchDsn,$clientsDB);
		$clickatellURL = "https://platform.clickatell.com/messages/http/send?apiKey=XGbb0jE3SD2rHwa4uh5OCQ%3D%3D";
	}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<!-- Latest compiled and minified CSS -->
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
		<!-- Override stylesheet -->
		<link rel="stylesheet" href="media/style/override.css"
		<!-- jQuery library -->
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
		<!-- Latest compiled JavaScript -->
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>	
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
	</head>
	<body>
		<?php
			error_reporting(!E_NOTICE);// EXPECTED ERROR: Notice: Undefined index: loginError;
			if(((count($_SESSION) == 0) || (is_null($_SESSION["signedInUser"]))) && (is_null($_SESSION["loginError"])))
			{
				error_reporting(E_ALL);
				$error = "You must be signed in to use this service";
				loginPage($error);
			}
			else if(!is_null($_SESSION["loginError"]))
			{
				error_reporting(E_ALL);
				$error = $_SESSION["loginError"];
				loginPage($error);
				unset ($_SESSION["loginError"]);
			}
			else if(($now - $_SESSION["lastActivity"]) > 3600)
			{
				error_reporting(E_ALL);
				$error = "Your session has expired. Please log in again";
				loginPage($error);
				session_destroy();
			}
			else //The user is logged in
			{
				$userInfo = $usersClient -> getDoc($_SESSION["signedInUser"]);
				$userSecurity = $userInfo -> platform -> security;
				//update Last Activity
				$_SESSION["lastActivity"] = $now;
				navbar();
				if($userSecurity -> monitoring -> activations)
				{
					alertBar();
				}
				//determine page to display
				if($_GET["page"] == "clients")
				{
					if(is_null($_GET["clientID"]))
					{
						clientList();
					}
					else
					{
						editClient();
					}
				}
				elseif($_GET["page"] == "users")
				{
					if(is_null($_GET["userID"]))
					{
						userList();
					}
					else
					{
						editUser();
					}
				}
				elseif($_GET["page"] == "signals")
				{
					if($userSecurity -> monitoring -> signals)
					{
						signalsPage();
					}
					else
					{
						unauthorised();
					}
				}
				elseif($_GET["page"] == "activations")
				{
error_reporting(E_ALL);
					activationsPage();
					?>
						<script>
							function updateActivations(retrieved)
							{
								//orderby priority
								{
									var actionPlans_ordered = {};
									//determine list of priorities
									{
										for(actionPlanName in retrieved.config.actionPlans)
										{
											var currentActionPlan = retrieved.config.actionPlans[actionPlanName];
											var priority = currentActionPlan.priority;
											if(priority !== null)
											{
												actionPlans_ordered[priority] = {};
											}
										}
									}
									//loop through actionPlans AGAIN, and then load them to a blank array
									{
										for(actionPlanName in retrieved.config.actionPlans)
										{
											var currentActionPlan = retrieved.config.actionPlans[actionPlanName];
											var priority = currentActionPlan.priority;
											if(priority !== null)
											{
												actionPlans_ordered[priority][currentActionPlan.name] = currentActionPlan;
											}
										}
									}
								}
								//collect client Activations
								{
									var activations_byPriority = {};
									for(var clientID in retrieved.clients)
									{	
										var currentClient = retrieved.clients[clientID];
										var clientActivations = currentClient.activations;
										if(clientActivations !== undefined)
										{
											//loop through actionPlans_ordered
											for(var priority in actionPlans_ordered)
											{
												for(var activationTypeName in actionPlans_ordered[priority])
												{
													if(clientActivations[activationTypeName] !== undefined)
													{
														for(var activationID in clientActivations[activationTypeName])
														{
															var currentActivation = clientActivations[activationTypeName][activationID];
															if(currentActivation.isActive)
															{
																activations_byPriority[priority] = {};
															}
														}
													}
												}
											}
										}
										
									}
									for(var clientID in retrieved.clients)
									{	
										var currentClient = retrieved.clients[clientID];
										var clientActivations = currentClient.activations;
										if(clientActivations !== undefined)
										{
											//loop through actionPlans_ordered
											for(var priority in actionPlans_ordered)
											{
												for(var activationTypeName in actionPlans_ordered[priority])
												{
													if(clientActivations[activationTypeName] !== undefined)
													{
														for(var activationID in clientActivations[activationTypeName])
														{
															var currentActivation = clientActivations[activationTypeName][activationID];
															if(currentActivation.isActive)
															{
																activations_byPriority[priority][activationID] = {};
															}
														}
													}
												}
											}
										}
										
									}
									for(var clientID in retrieved.clients)
									{	
										var currentClient = retrieved.clients[clientID];
										var clientActivations = currentClient.activations;
										if(clientActivations !== undefined)
										{
											//loop through actionPlans_ordered
											for(var priority in actionPlans_ordered)
											{
												for(var activationTypeName in actionPlans_ordered[priority])
												{
													if(clientActivations[activationTypeName] !== undefined)
													{
														for(var activationID in clientActivations[activationTypeName])
														{
															var currentActivation = clientActivations[activationTypeName][activationID];
															if(currentActivation.isActive)
															{
																activations_byPriority[priority][activationID]['clientDetails'] = currentClient;
																activations_byPriority[priority][activationID]['activationDetails'] = currentActivation;
																activations_byPriority[priority][activationID]['activationType'] = activationTypeName;
															}
														}
													}
												}
											}
										}
										
									}
								}
								//retrieve data from sorted activations to display data
								{
									var activationsContent = document.getElementById('activationsContent');
									activationsContent.innerHTML='';
									for(var priority in activations_byPriority)
									{
										for(var activationID in activations_byPriority[priority])
										{
											var currentActivation = activations_byPriority[priority][activationID];
											var activationName = currentActivation.activationType;
											var clientID = currentActivation.clientDetails._id;
											var clientName = currentActivation.clientDetails.clientName;
											var symbol = retrieved.config.actionPlans[activationName].symbol;
											var dateCreated = currentActivation.activationDetails.dateTime;
											//create well
											{
												var activationWell = document.createElement('DIV');
												activationWell.classList.add('activationWell');
												activationWell.classList.add('well');
												activationWell.setAttribute('id', activationID);
											}
											//create activation link
											{
												var activationLink = document.createElement('a');
												activationLink.setAttribute('href',`?page=clients&clientID=${clientID}&tab=activations&activationID=${activationID}`);
												activationLink.classList.add('activationLink');
											}
											//create activationDetails
											{ 
												var activationDetails = document.createElement('div');
												activationDetails.classList.add('activationDetails');
												activationDetails.innerHTML=`<h3><span>${symbol}</span> ${clientName}</h3><span>${dateCreated} - ${activationName}</span>`;
											}
											//create activation signals
											{
												var activationSignals = document.createElement('ul');
												activationSignals.classList.add('activationSignals');
												var assignedSignals = currentActivation.activationDetails.assignedSignals;
												for(i=0; i<assignedSignals.length ; i++)
												{
													signalID = assignedSignals[i];
													currentSignal = retrieved.signals[signalID];
													var signalDetails = document.createElement('li');
													var signalDate = currentSignal.dateTime;
													var signalMessage = currentSignal.signal.eventName;
													if(retrieved.config.actionPlans[activationName].signalType == "zone info")
													{
														signalMessage += ` Zone: ${currentSignal.signal.zone_user}`;
													}
													else if(retrieved.config.actionPlans[activationName].signalType == "user info")
													{
														//see if client has users
														var currentClient = retrieved.clients[clientID];
														clientUsers = currentClient.users;
														if(clientUsers !== undefined)
														{
															userNumber = currentSignal.signal.zone_user;
															var userFound = false;
															for(userID in clientUsers)
															{
																currentUser = clientUsers[userID];
																if(currentUser.userNumber == currentSignal.signal.zone_user)
																{
																	userFound = true;
																	signalMessage += ` ${retrieved.users[userID].details.name}`;
																}
															}
															if(userFound === false)
															{
																signalMessage += ` User: ${userNumber}`;
															}
														}
														else
														{
															signalMessage += ` User: ${currentSignal.signal.zone_user}`;
														}
													}
													signalDetails.innerText = `${signalDate} - ${signalMessage}`;
													activationSignals.appendChild(signalDetails);
													
												}
											}
											//add all child elements
											{
												activationLink.appendChild(activationDetails);
												activationLink.appendChild(activationSignals);
												activationWell.appendChild(activationLink);
												activationsContent.appendChild(activationWell);
											}
										}
									}
								}
							}
						</script>
					<?php
				}
				else//No page found, Display Dashboard
				{
					$_GET["page"] = 'dashboard';
					dashboard();
					?>
						<script>
							function updateActivationsDashboard(retrieved)
							{
								//orderby priority
								{
									var actionPlans_ordered = {};
									//determine list of priorities
									{
										for(actionPlanName in retrieved.config.actionPlans)
										{
											var currentActionPlan = retrieved.config.actionPlans[actionPlanName];
											var priority = currentActionPlan.priority;
											if(priority !== null)
											{
												actionPlans_ordered[priority] = {};
											}
										}
									}
									//loop through actionPlans AGAIN, and then load them to a blank array
									{
										for(actionPlanName in retrieved.config.actionPlans)
										{
											var currentActionPlan = retrieved.config.actionPlans[actionPlanName];
											var priority = currentActionPlan.priority;
											if(priority !== null)
											{
												actionPlans_ordered[priority][currentActionPlan.name] = currentActionPlan;
											}
										}
									}
								}
								//collect client Activations
								{
									var activations_byPriority = {};
									for(var clientID in retrieved.clients)
									{	
										var currentClient = retrieved.clients[clientID];
										var clientActivations = currentClient.activations;
										if(clientActivations !== undefined)
										{
											//loop through actionPlans_ordered
											for(var priority in actionPlans_ordered)
											{
												for(var activationTypeName in actionPlans_ordered[priority])
												{
													if(clientActivations[activationTypeName] !== undefined)
													{
														for(var activationID in clientActivations[activationTypeName])
														{
															var currentActivation = clientActivations[activationTypeName][activationID];
															if(currentActivation.isActive)
															{
																activations_byPriority[priority] = {};
															}
														}
													}
												}
											}
										}
										
									}
									for(var clientID in retrieved.clients)
									{	
										var currentClient = retrieved.clients[clientID];
										var clientActivations = currentClient.activations;
										if(clientActivations !== undefined)
										{
											//loop through actionPlans_ordered
											for(var priority in actionPlans_ordered)
											{
												for(var activationTypeName in actionPlans_ordered[priority])
												{
													if(clientActivations[activationTypeName] !== undefined)
													{
														for(var activationID in clientActivations[activationTypeName])
														{
															var currentActivation = clientActivations[activationTypeName][activationID];
															if(currentActivation.isActive)
															{
																activations_byPriority[priority][activationID] = {};
															}
														}
													}
												}
											}
										}
										
									}
									for(var clientID in retrieved.clients)
									{	
										var currentClient = retrieved.clients[clientID];
										var clientActivations = currentClient.activations;
										if(clientActivations !== undefined)
										{
											//loop through actionPlans_ordered
											for(var priority in actionPlans_ordered)
											{
												for(var activationTypeName in actionPlans_ordered[priority])
												{
													if(clientActivations[activationTypeName] !== undefined)
													{
														for(var activationID in clientActivations[activationTypeName])
														{
															var currentActivation = clientActivations[activationTypeName][activationID];
															if(currentActivation.isActive)
															{
																activations_byPriority[priority][activationID]['clientDetails'] = currentClient;
																activations_byPriority[priority][activationID]['activationDetails'] = currentActivation;
																activations_byPriority[priority][activationID]['activationType'] = activationTypeName;
															}
														}
													}
												}
											}
										}
										
									}
								}
								//retrieve data from sorted activations to display data
								{
									var dashboardContent = document.getElementById('activationDashboard_content');
									dashboardContent.innerHTML='';
									for(var priority in activations_byPriority)
									{
										for(var activationID in activations_byPriority[priority])
										{
											var currentActivation = activations_byPriority[priority][activationID];
											var activationName = currentActivation.activationType;
											var clientID = currentActivation.clientDetails._id;
											var clientName = currentActivation.clientDetails.clientName;
											var symbol = retrieved.config.actionPlans[activationName].symbol;
											var dateCreated = currentActivation.activationDetails.dateTime;
											//create well
											{
												var activationWell = document.createElement('DIV');
												activationWell.classList.add('activationWell');
												activationWell.classList.add('well');
												activationWell.setAttribute('id', activationID);
											}
											//create activation link
											{
												var activationLink = document.createElement('a');
												activationLink.setAttribute('href',`?page=clients&clientID=${clientID}&tab=activations&activationID=${activationID}`);
												activationLink.classList.add('activationLink');
											}
											//create activationDetails
											{ 
												var activationDetails = document.createElement('div');
												activationDetails.classList.add('activationDetails');
												activationDetails.innerHTML=`<h3><span>${symbol}</span> ${clientName}</h3><span>${dateCreated} - ${activationName}</span>`;
											}
											//create activation signals
											{
												var activationSignals = document.createElement('ul');
												activationSignals.classList.add('activationSignals');
												var assignedSignals = currentActivation.activationDetails.assignedSignals;
												for(i=0; i<assignedSignals.length ; i++)
												{
													signalID = assignedSignals[i];
													currentSignal = retrieved.signals[signalID];
													var signalDetails = document.createElement('li');
													var signalDate = currentSignal.dateTime;
													var signalMessage = currentSignal.signal.eventName;
													if(retrieved.config.actionPlans[activationName].signalType == "zone info")
													{
														signalMessage += ` Zone: ${currentSignal.signal.zone_user}`;
													}
													else if(retrieved.config.actionPlans[activationName].signalType == "user info")
													{
														//see if client has users
														var currentClient = retrieved.clients[clientID];
														clientUsers = currentClient.users;
														if(clientUsers !== undefined)
														{
															userNumber = currentSignal.signal.zone_user;
															var userFound = false;
															for(userID in clientUsers)
															{
																currentUser = clientUsers[userID];
																if(currentUser.userNumber == currentSignal.signal.zone_user)
																{
																	userFound = true;
																	signalMessage += ` ${retrieved.users[userID].details.name}`;
																}
															}
															if(userFound === false)
															{
																signalMessage += ` User: ${userNumber}`;
															}
														}
														else
														{
															signalMessage += ` User: ${currentSignal.signal.zone_user}`;
														}
													}
													signalDetails.innerText = `${signalDate} - ${signalMessage}`;
													activationSignals.appendChild(signalDetails);
													
												}
											}
											//add all child elements
											{
												activationLink.appendChild(activationDetails);
												activationLink.appendChild(activationSignals);
												activationWell.appendChild(activationLink);
												dashboardContent.appendChild(activationWell);
											}
										}
									}
								}
							}
							function updateOpenClientsDashboard(retrieved)
							{
								var dashboard = document.getElementById('openClientsDashboard_content');
								dashboard.innerHTML='';
								var openCount = 0;
								var closedCount = 0;
								var openStores = document.createElement('div');
								openStores.classList.add('well');
								openStores.classList.add('openClients_open');
								var openStoreCounter = document.createElement('h2');
								var closedStores = document.createElement('div');
								closedStores.classList.add('well');
								closedStores.classList.add('openClients_closed');
								var closedStoreCounter = document.createElement('h2');
								var openList = document.createElement('ul');
								openList.classList.add('openClients_list');
								var closedList = document.createElement('ul');
								closedList.classList.add('openClients_list');
								for(var clientID in retrieved.clients)
								{
									var currentClient = retrieved.clients[clientID];
									var clientName = currentClient.clientName;
									if(currentClient.currentStatus == "open")
									{
										status = 'open';
										openCount += 1;
										var clientDetails = document.createElement('li');
										clientDetails.innerHTML = `<a href="?page=clients&clientID=${clientID}">${clientName}</a>`;
										openList.appendChild(clientDetails);
									}
									else
									{
										status = "closed";
										closedCount += 1;
										var clientDetails = document.createElement('li');
										clientDetails.innerHTML = `<a href="?page=clients&clientID=${clientID}">${clientName}</a>`;
										closedList.appendChild(clientDetails);
									}
								}
								closedStoreCounter.innerHTML=`&#x1F512 Closed Stores (${closedCount})`;
								closedStores.appendChild(closedStoreCounter);
								closedStores.appendChild(closedList);
								dashboard.appendChild(closedStores);
								openStoreCounter.innerHTML=`&#x1F513 Open Stores (${openCount})`;
								openStores.appendChild(openStoreCounter);
								openStores.appendChild(openList);
								dashboard.appendChild(openStores);
							}
						</script>
					<?php
				}
				?>
					<script>
						signedInUser = "<?php echo $_SESSION["signedInUser"]; ?>";
						retrieveData();
						function retrieveData()
						{
							$.ajax({
								url:"retrieve.php",
								async:true,
								type:"post",
								data:{token:"<?php echo $_SESSION["signedInUser"]?>",require:"all"},
								dataType:"JSON",
								cache: false,
								success:function(msg){
//console.log(msg);
									<?php
										if(($userSecurity -> monitoring -> activations) && ($userSecurity -> monitoring -> enable))
										{
											echo "updateAlertBar(msg.retrieved);";
										}
										//Dashboard Functions
										{
											if(($userSecurity -> dashboard -> activations) && ($userSecurity -> dashboard -> enable) && ($_GET["page"] == "dashboard"))
											{
												echo "updateActivationsDashboard(msg.retrieved);";
											}
											if(($userSecurity -> dashboard -> openClients) && ($userSecurity -> dashboard -> enable) && ($_GET["page"] == "dashboard"))
											{
												echo "updateOpenClientsDashboard(msg.retrieved);";
											}
										}
										//signals functions
										{
											if(($userSecurity -> monitoring -> signals) && ($userSecurity -> monitoring -> enable) && ($_GET["page"] == "signals"))
											{
												echo "updateSignals(msg.retrieved);";
											}
										}
										//activations function
										{
											if(($userSecurity -> monitoring -> activations) && ($userSecurity -> monitoring -> enable) && ($_GET["page"] == "activations"))
											{
												echo "updateActivations(msg.retrieved);";
											}
										}
									?>
									retrieveData();
								}
							});
						}
						<?php
/*LATER DEVELOPMENT							
							if(($userSecurity -> dashboard -> onlineDevices) && ($userSecurity -> dashboard -> enable) && ($_GET["page"] == "dashboard"))
							{
								echo "updateOnlineDevicesDashboard(0);";
							}
*/
						?>
						function updateAlertBar(retrieved)
						{
							var alerts = document.getElementById('alertBar_alerts')
							alerts.innerHTML='';
							//orderby priority
							{
								var actionPlans_ordered = {};
								//determine list of priorities
								{
									for(actionPlanName in retrieved.config.actionPlans)
									{
										var currentActionPlan = retrieved.config.actionPlans[actionPlanName];
										var priority = currentActionPlan.priority;
										if(priority !== null)
										{
											actionPlans_ordered[priority] = {};
										}
									}
								}
								//loop through actionPlans AGAIN, and then load them to a blank array
								{
									for(actionPlanName in retrieved.config.actionPlans)
									{
										var currentActionPlan = retrieved.config.actionPlans[actionPlanName];
										var priority = currentActionPlan.priority;
										if(priority !== null)
										{
											actionPlans_ordered[priority][currentActionPlan.name] = currentActionPlan;
										}
									}
								}
							}
							//count client activations
							{
								var count = {};
								var clients = retrieved.clients;
								for(var clientID in clients)
								{
									var currentClient = clients[clientID];
									var clientActivations = currentClient.activations;
									for(var activationType in clientActivations)
									{
										count[activationType] = 0 ;
									}
								}
								for(var clientID in clients)
								{
									var currentClient = clients[clientID];
									var clientActivations = currentClient.activations;
									for(var activationType in clientActivations)
									{
										var currentActivationType = clientActivations[activationType];
										for(activationID in currentActivationType)
										{
											if(currentActivationType[activationID].isActive)
											{
												count[activationType] += 1 ;
											}
										}
									}
								}
							}
							//loop through ordered action plans to display alert bar
							{
								for(var priority in actionPlans_ordered)
								{
									var actionPlans_byPriority = actionPlans_ordered[priority];
									for(var actionPlanName in actionPlans_byPriority)
									{
										if(count[actionPlanName] > 0)
										{
											var currentActionPlan = actionPlans_byPriority[actionPlanName];
											var node = document.createElement("LI");
											node.innerHTML = `<a class="alertBar_alert" href="?page=activations"><span class=alertBar_alertIcon>${currentActionPlan.symbol}</span> ${count[actionPlanName]}</a>`;
											alerts.appendChild(node);
										}
									}
								}
							}
						}
						function unixToTime(unix)
						{
							var dt = new Date(unix*1000);
							var day = dt.getDate();
							var month = dt.getMonth() + 1;
							var year = dt.getFullYear();
							var hr = dt.getHours();
							var m = "0" + dt.getMinutes();
							var s = "0" + dt.getSeconds();
							return `${day}/${month}/${year} @ ${hr}:${m.substr(-2)}:${s.substr(-2)}`;
						}
					</script>
					<script src="/libraries/hsimp/build/hsimp.min.js"></script>
				<?php
			}
		?>
		
	</body>
</html>
<?php
	//pages
	{
		function loginPage($error)
		{
			?>
				<div class="container-fluid fullHeight">
					<div class="col-sm-3"></div>
					<div class="col-sm-6">
						<div class="well">
							<div class="alert alert-warning">
								<strong>Warning! </strong><?php echo $error; ?>
							</div>
							<form method="POST" action="login.php" id="loginForm">
								<input type="hidden" id="returnTo" name="returnTo">
								<script>
									document.getElementById('returnTo').value = `${window.location.search}${window.location.hash}`;
								</script>
								<div class="form-group">
									<label for="email">Email address:</label>
									<input required name="email" type="email" class="form-control" id="email">
								</div>
								<div class="form-group">
									<label for="pwd">Password:</label>
									<input required name="password" type="password" class="form-control" id="pwd">
								</div>
								<input type="submit" class="btn btn-default" name="submit" value="Log In">
							</form>
						</div>
					</div>
					<div class="col-sm-3"></div>
				</div>
			<?php
		}
		function navbar()
		{
			global $userInfo, $userSecurity;
			//$userSecurity = $userInfo -> platform -> security;
			?>
				<nav class="navbar navbar-inverse navbar-fixed-top">
					<script>
						function navHover(element)
						{
							element.getElementsByTagName('a')[0].setAttribute("aria-expanded",true);
							element.classList.add("open");
						}
						function navUnhover(element)
						{
							element.getElementsByTagName('a')[0].setAttribute("aria-expanded",false);
							element.classList.remove("open");
						}
					</script>
					<div class="container-fluid">
						<div class="navbar-header">
							<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>                        
							</button>
							<a class="navbar-brand" href="/">COREmonitor</a>
						</div>
						<div>
							<div class="collapse navbar-collapse" id="myNavbar">
								<ul class="nav navbar-nav">
									<?php
										if($userSecurity -> monitoring -> enable)//test if monitoing is enabled to display menu item;
										{
											?>
												<li class="dropdown <?php if(($_GET["page"] == "signals") || ($_GET["page"] == "activations")){ echo "active"; } ?>" onmouseover="navHover(this);" onmouseout="navUnhover(this);">
													<a class="dropdown-toggle" data-toggle="dropdown">
														<span class="glyphicon glyphicon-eye-open"></span> Monitoring <span class="caret"></span>
													</a>
													<ul class="dropdown-menu">
														<?php
															if($userSecurity -> monitoring -> signals)
															{
																?>
																	<li <?php if($_GET["page"] == "signals"){ echo "class=\"active\""; } ?>>
															<a href="?page=signals">
																<span class="glyphicon glyphicon-signal"></span> Signals
															</a>
														</li>
																<?php
															}
															if($userSecurity -> monitoring -> activations)
															{
																?>
																	<li <?php if($_GET["page"] == "activations"){ echo "class=\"active\""; } ?>>
															<a href="?page=activations">
																<span class="glyphicon glyphicon-exclamation-sign"></span> Activations
															</a>
														</li>
													<?php
												}
											?>
										</ul>
									</li>
											<?php
										}
										if($userSecurity -> clients -> enable)
										{
											?>
												<li <?php if($_GET["page"] == "clients"){ echo "class=\"active\""; } ?>>
													<a href="?page=clients">
														<span class="glyphicon glyphicon-star-empty"></span> Clients
													</a>
												</li>
											<?php
										}
										if($userSecurity -> users -> enable)
										{
											?>
												<li <?php if($_GET["page"] == "users"){ echo "class=\"active\""; } ?>>
													<a href="?page=users">
														<span class="glyphicon glyphicon-user"></span> Users
													</a>
												</li>
											<?php
										}
										if($userSecurity -> reports -> enable)
										{
											?>
												<li <?php if($_GET["page"] == "reports"){ echo "class=\"active\""; } ?>>
													<a href="?page=reports">
														<span class="glyphicon glyphicon-book"></span> Reports
													</a>
												</li>
											<?php
										}
									?>
									<li class="dropdown" onmouseover="navHover(this);" onmouseout="navUnhover(this);">
										<a class="dropdown-toggle" data-toggle="dropdown">
											<span class="glyphicon glyphicon-asterisk"></span> Options <span class="caret"></span>
										</a>
										<ul class="dropdown-menu">
											<li>
												<a href="logout.php">
													<span class="glyphicon glyphicon-off"></span> Log Off
												</a>
											</li>
											<li onclick="updateProfile();">
												<a>
													<span class="glyphicon glyphicon-folder-open"></span> Update Profile
												</a>
											</li>
										</ul>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</nav>
				<nav class="navbar navbar-inverse">
					<script>
						function navHover(element)
						{
							element.getElementsByTagName('a')[0].setAttribute("aria-expanded",true);
							element.classList.add("open");
						}
						function navUnhover(element)
						{
							element.getElementsByTagName('a')[0].setAttribute("aria-expanded",false);
							element.classList.remove("open");
						}
					</script>
					<div class="container-fluid">
						<div class="navbar-header">
							<button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#myNavbar">
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>
								<span class="icon-bar"></span>                        
							</button>
							<a class="navbar-brand" href="/">COREmonitor</a>
						</div>
						<div>
							<div class="collapse navbar-collapse" id="myNavbar">
								<ul class="nav navbar-nav">
									<?php
										if($userSecurity -> monitoring -> enable)//test if monitoing is enabled to display menu item;
										{
											?>
												<li class="dropdown <?php if(($_GET["page"] == "signals") || ($_GET["page"] == "activations")){ echo "active"; } ?>" onmouseover="navHover(this);" onmouseout="navUnhover(this);">
													<a class="dropdown-toggle" data-toggle="dropdown">
														<span class="glyphicon glyphicon-eye-open"></span> Monitoring <span class="caret"></span>
													</a>
													<ul class="dropdown-menu">
														<?php
															if($userSecurity -> monitoring -> signals)
															{
																?>
																	<li <?php if($_GET["page"] == "signals"){ echo "class=\"active\""; } ?>>
															<a href="?page=signals">
																<span class="glyphicon glyphicon-signal"></span> Signals
															</a>
														</li>
																<?php
															}
															if($userSecurity -> monitoring -> activations)
															{
																?>
																	<li <?php if($_GET["page"] == "activations"){ echo "class=\"active\""; } ?>>
															<a href="?page=activations">
																<span class="glyphicon glyphicon-exclamation-sign"></span> Activations
															</a>
														</li>
													<?php
												}
											?>
										</ul>
									</li>
											<?php
										}
										if($userSecurity -> clients -> enable)
										{
											?>
												<li <?php if($_GET["page"] == "clients"){ echo "class=\"active\""; } ?>>
													<a href="?page=clients">
														<span class="glyphicon glyphicon-star-empty"></span> Clients
													</a>
												</li>
											<?php
										}
										if($userSecurity -> users -> enable)
										{
											?>
												<li <?php if($_GET["page"] == "users"){ echo "class=\"active\""; } ?>>
													<a href="?page=users">
														<span class="glyphicon glyphicon-user"></span> Users
													</a>
												</li>
											<?php
										}
										if($userSecurity -> reports -> enable)
										{
											?>
												<li <?php if($_GET["page"] == "reports"){ echo "class=\"active\""; } ?>>
													<a href="?page=reports">
														<span class="glyphicon glyphicon-book"></span> Reports
													</a>
												</li>
											<?php
										}
									?>
									<li class="dropdown" onmouseover="navHover(this);" onmouseout="navUnhover(this);">
										<a class="dropdown-toggle" data-toggle="dropdown">
											<span class="glyphicon glyphicon-asterisk"></span> Options <span class="caret"></span>
										</a>
										<ul class="dropdown-menu">
											<li>
												<a href="logout.php">
													<span class="glyphicon glyphicon-off"></span> Log Off
												</a>
											</li>
											<li onclick="updateProfile();">
												<a>
													<span class="glyphicon glyphicon-folder-open"></span> Update Profile
												</a>
											</li>
										</ul>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</nav>
			<?php
		}
		function alertBar()
		{
			global $userinfo;
			?>
				<div class="container">
					<div>
						<div class="">
							<ul class="nav" id="alertBar_alerts"></ul>
						</div>
					</div>
				</div>
			<?php
		}
		function dashboard()
		{
			global $userSecurity;
			?>
				<title>COREmonitor || Dashboard</title>
				<div class="container-fluid">
					<?php
						if($userSecurity -> dashboard -> enable)
						{
							$dashboardColumns = 0;
							if($userSecurity -> dashboard -> activations)
							{
								$dashboardColumns += 1;
							}
							if($userSecurity -> dashboard -> onlineDevices)
							{
								$dashboardColumns += 1;
							}
							if($userSecurity -> dashboard -> openClients)
							{
								$dashboardColumns += 1;
							}
							if($userSecurity -> dashboard -> activations)
							{
								$activationsDash = activationsDash(12/$dashboardColumns);
							}
							if($userSecurity -> dashboard -> onlineDevices)
							{
								$onlineDevicesDash = onlineDevicesDash(12/$dashboardColumns);
							}
							if($userSecurity -> dashboard -> openClients)
							{
								$openClientsDash = openClientsDash(12/$dashboardColumns);
							}
						}
					?>
				</div>
			<?php
		}
		function activationsDash($colspan)
		{
			?>
				<div class="col-md-<?php echo($colspan);?>">
					<div class="panel panel-default">
						<div class="panel-heading">Current Activations</div>
						<div class="panel-body" id="activationDashboard_content"></div>
					</div>
				</div>
			<?php
		}
		function onlineDevicesDash($colspan)
		{
			?>
				<div class="col-md-<?php echo($colspan);?>">
					<div class="panel panel-default">
						<div class="panel-heading">Online Devices</div>
						<div class="panel-body" id="onlineDevicesDashboard_content"></div>
					</div>
				</div>
			<?php
		}
		function openClientsDash($colspan)
		{
			?>
				<div class="col-md-<?php echo($colspan);?>">
					<div class="panel panel-default">
						<div class="panel-heading">Store Status</div>
						<div class="panel-body" id="openClientsDashboard_content">
							
						</div>
					</div>
				</div>
			<?php
		}
		function clientList()
		{
			global $clientsClient, $userSecurity;
			if($userSecurity -> clients -> enable)
			{
				?>
					<title>COREmonitor || Clients</title>
					<div class="container">
						<button style="float:right;" class="btn btn-success" onclick="window.location.href='?page=clients&clientID=new';">Add New Client</button><br>
						<br>
						<input class="form-control" id="clientSearch" type="text" placeholder="Search..">
						<br>
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th>Client Name</th>
									<th>address</th>
								</tr>
							</thead>
							<tbody id="clientTable">
								<?php
									$allClients = $clientsClient -> getAllDocs();
									$clients_sorted = array();
									$clientNames = array();
									foreach($allClients -> rows as $currentRow)
									{
										$clientID = $currentRow -> id;
										$currentClient = $clientsClient -> getDoc($clientID);
										$clientName = $currentClient -> clientName;
										$clientNames[] = "$clientName $clientID";
										$clients_sorted["$clientName $clientID"] = $currentClient;
										
									}
									sort($clientNames);
									//loop through clientNames
									foreach($clientNames as $currentClientName)
									{
										$currentClient = $clients_sorted[$currentClientName];
										$clientID = $currentClient -> _id;
										$clientName = $currentClient -> clientName;
										$address = $currentClient -> address -> line1;
										$address .= ",<br>";
										$address .= $currentClient -> address -> line2;
										$address .= ", " . $currentClient -> address -> postCode;
										?>
										<tr>
											<td style="<?php if($userSecurity -> clients -> edit){ echo "cursor:pointer;";}else{echo"cursor:not-allowed;";}?>" <?php if($userSecurity -> clients -> edit){ echo "onclick=\"window.location.href='?page=clients&clientID=$clientID';\"";}?> ><?php echo $clientName ?></td>
											<td style="<?php if($userSecurity -> clients -> edit){ echo "cursor:pointer;";}else{echo"cursor:not-allowed;";}?>" <?php if($userSecurity -> clients -> edit){ echo "onclick=\"window.location.href='?page=clients&clientID=$clientID';\"";}?> ><?php echo $address ?></td>
										</tr>
										<?php
									}
								?>
							</tbody>
						</table>
						<script>
							$(document).ready(function()
							{
								$("#clientSearch").on("keyup", function()
								{
									var value = $(this).val().toLowerCase();
									$("#clientTable tr").filter(function()
									{
										$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
									});
								});
							});
							</script>
					</div>
				<?php
			}
			else
			{
				unauthorised();
			}
		}
		function editClient()
		{
			global $clientsClient, $userSecurity, $configClient, $usersClient, $signalsClient;
			if(($userSecurity -> clients -> edit))
			{
				echo "<title>COREmonitor || Edit Client</title>";
				if($_GET["clientID"] == "new")
				{
					//determine Next clientID
					{
						$clientIDs = array();
						$max = 0;
						$next = 9999;
						$allClients = $clientsClient -> getAllDocs() -> rows;
						foreach($allClients as $currentRow)
						{
							$clientID = $currentRow -> id;
							if($clientID !== "SYST")
							{
								$clientID_intval = intval($clientID);
								if($clientID_intval >= $max)
								{
									$max = $clientID_intval;
								}
								$clientIDs[$clientID_intval] = $clientID;
							}
						}
						$newIDFound = false;
						for($x = 1; $x <= $max; $x++)
						{
							if(is_null($clientIDs[$x]))
							{
								if(($x<$next))
								{
									$next = $x;
									$newIDFound = true;
								}
							}
						}
						if(($newIDFound))
						{
							$newID = str_pad($next, 4, '0', STR_PAD_LEFT);
						}
						else
						{
							$newID = str_pad($max + 1, 4, '0', STR_PAD_LEFT);
						}
					}
					$doc = new stdClass();
					$doc -> clientName = "New Client";
					$doc -> _id = $newID;
					$_GET["clientID"] = $clientsClient -> storeDoc($doc) -> id;
					echo "<script>window.location.search=\"?page=" . $_GET["page"] . "&clientID=" . $_GET["clientID"] . "\";</script>";
				}
				try
				{
					$client = $clientsClient -> getDoc($_GET["clientID"]);
				}
				catch (Exception $e)
				{
					echo "<script>window.alert('".  $e->getMessage(). "'); window.location.href='?page=clients';</script>";
				}
				//test if there are any active activations
				{
					foreach($client -> activations as $currentActivationType)
					{
						foreach($currentActivationType as $currentActivation)
						{
							if($currentActivation -> isActive)
							{
								$activeActivationsFound = true;
							}
						}
					}
				}
				$clientUsers = $client -> users;
				?>
					<div class="container-fluid">
						<h1><?php echo $client -> clientName ." (" . $_GET["clientID"] .")"; ?></h1>
						<div class="col-sm-3">
							<ul class="nav nav-pills nav-stacked">
								<?php
									if($activeActivationsFound)
									{
										?>
											<li class="active"><a data-toggle="pill" href="#activations">Activations</a></li>
										<?php
									}
								?>
								<li <?php if(!$activeActivationsFound){echo "class=\"active\"";}?>><a data-toggle="pill" href="#contact">Contact Details</a></li>
								<li><a data-toggle="pill" href="#users">Users</a></li>
								<li><a data-toggle="pill" href="#zones">Zones</a></li>
								<li><a data-toggle="pill" href="#tests">Tests</a></li>
							</ul>
						</div>
						<div class="col-sm-9">
							<div class="tab-content">
								<?php
									if($activeActivationsFound)
									{
										?>
											<div id="activations" class="tab-pane fade in active">
												<h3>Activations</h3>
												<script>
													function displayActivation(link)
													{
														var links = link.parentElement.parentElement.getElementsByTagName('li');
														for(var i = 0; i< links.length; i++)
														{
															links[i].classList.remove('active');
														}
														var href = link.getAttribute('href');
														var activationID = href.replace('#','activation_');
														var activationDetails = document.getElementById('activationDetails');
														var activations = activationDetails.children;
														for(var i = 0; i< activations.length;i++)
														{
															activations[i].style.display="none";
														}
														document.getElementById(activationID).style.display="block";
														link.parentElement.classList.add('active');
													}
												</script>
												<div class="container-fluid">
													<div class="col-sm-3">
														<ul class="nav nav-pills nav-stacked" id="activationList" ></ul>
													</div>
													<div id="activationDetails" class="col-sm-9">
														<div class="modal fade" id="passwordVerificationModal" role="dialog">
															<div class="modal-dialog modal-sm">
																<div class="modal-content">
																	<div class="modal-header">
																		<h4 class="modal-title">Please Verify OK Password:</h4>
																	</div>
																	<div class="modal-body">
																		<label for="passwordVerificationInput">Password:</label>
																		<input onclick="testPasswordVerification(this);" onKeyup="testPasswordVerification(this);" spellcheck="true" type="text" id="passwordVerificationInput" class="form-control">
																		<input type="hidden" id="passwordVerification_responseNoteID">
																		<div id="passwordVerificationNotice"></div>
																		<span style="text-align:center"><button class="btn btn-danger" onclick="contactOffSite();">Contact not on site</button></span>
																	</div>
																</div>
															</div>
														</div>
													</div>
													<script>
														var completedActivations = Array();
														var completedActivationCount = 0;
														clientUpdater("<?php echo ($_GET["clientID"]); ?>");
														function clientUpdater(clientID)
														{
															$.ajax({
																url:"retrieve.php",
																async:true,
																type:"post",
																data:{token:"<?php echo $_SESSION["signedInUser"]?>",require:"clientUpdater",clientID:`${clientID}`},
																dataType:"JSON",
																cache: false,
																success:function(msg){
																	retrieved = msg.retrieved;
																	clientPasswords = msg.retrieved.client.passwords;
																	updateClientActivations(msg.retrieved);
																	updateActivationSignals(msg.retrieved);
																	clientUpdater(clientID);
																}
															});
														}
														function updateClientActivations(retrieved)
														{
															var clientActivations = retrieved.client.activations;
															for(var activationType in clientActivations)
															{
																var currentActivationType = clientActivations[activationType];
																for(var activationID in currentActivationType)
																{
																	var currentActivation = currentActivationType[activationID];
																	if(currentActivation.isActive)
																	{
																		if(document.getElementById(`activation_${activationID}`) == null)
																		{
																			if(completedActivations.indexOf(activationID) === -1);
																			{
																				//add activation
																				{
																					var activationDetails = document.getElementById('activationDetails');
																					var activation = document.createElement('div');
																					activation.classList.add('activationContent');
																					activation.setAttribute('onkeyup',`updateClient('activations')`);
																					activation.setAttribute('onclick',`updateClient('activations')`);
																					var activationHeading = document.createElement('h4');
																					activationHeading.innerText = `${activationType} - ${activationID}`;
																					activation.appendChild(activationHeading);
																					var completeButton = document.createElement('button');
																					completeButton.classList.add('btn');
																					completeButton.classList.add('btn-success');
																					completeButton.innerHTML = `<span class="glyphicon glyphicon-ok"></span> Complete Activation`;
																					completeButton.setAttribute('type','button');
																					completeButton.setAttribute('onclick',`addResponseNote(${activationID},"Activation Completed");`);
	//completeButton.setAttribute('onclick',`addResponseNote(${activationID},"Activation Complete");`);
																					activation.appendChild(completeButton);
																					var activationContainer = document.createElement('div');
																					activationContainer.classList.add('container-fluid');
																					activation.appendChild(activationContainer);
																					activation.setAttribute('id',`activation_${activationID}`);
																					activationDetails .appendChild(activation);
																					var signalContainer = document.createElement('div');
																					signalContainer.setAttribute('id',`signalContainer_${activationID}`);
																					signalContainer.classList.add('container-fluid');
																					signalContainer.innerHTML=`<h4 style="text-align:center;">Signals</h4>`
																					activation.appendChild(signalContainer);
																				}
																				//add activation link
																				{
																					var activationList = document.getElementById('activationList');
																					var activationLink = document.createElement('li');
																					activationList.appendChild(activationLink);
																					activationLink.setAttribute('id',`activationLink_${activationID}`);
																					activationLink.innerHTML = `<a onclick="displayActivation(this);" href="#${activationID}">${activationType} - ${activationID}</a>`;
																				}
																				//add responses
																				{
																					var responseColumn = document.createElement('div');
																					activationContainer.appendChild(responseColumn);
																					responseColumn.classList.add("col-sm-6");
																					responseColumn.setAttribute('id',`responseNotes_${activationID}`);
																					responseColumn.innerHTML = `<h4 style="text-align:center;">Response Notes</h4>`;
																					responseColumn.innerHTML += `<button onclick="addResponseNote(${activationID});" type="button" class="btn btn-success">Add Response Note</button>`;
																					//collect responses
																					{
																						var activationResponses = currentActivation.responses;
																						if(activationResponses != undefined)
																						{
																							for(responseID in activationResponses)
																							{
																								var responseNote = document.createElement('div');
																								responseNote.classList.add('well');
																								responseNote.classList.add('activationResponse');
																								responseNote.classList.add('activationResponse');
																								responseNote.setAttribute('id',`response_${responseID}`);
																								var currentResponse = activationResponses[responseID];
																								var responseBy = currentResponse.responseBy;
																								var responder = retrieved.users[responseBy].details.name;
																								var responseContent = currentResponse.content;
																								responseNote.innerHTML = `<h4>${unixToTime(responseID)}:<br>${responder}</h4>`;
																								responseNote.innerHTML += `<textarea system_responseBy="${responseBy}" class="form-control" name="responseContent_${responseID}" id="responseContent_${responseID}">${responseContent}</textArea>`;
																								responseColumn.appendChild(responseNote);
																							}
																						}
																					}
																					var addButton = document.createElement('button');
																					addButton.classList.add('btn');
																					addButton.classList.add('btn-success');
																					addButton.innerText = "Add Response Note";
																					addButton.setAttribute('type','button');
																					addButton.setAttribute('onclick',`addResponseNote(${activationID})`);
																					responseColumn.appendChild(addButton);
																				}
																				//add contacts
																				{
																					var contactsColumn = document.createElement('div');
																					activationContainer.appendChild(contactsColumn);
																					contactsColumn.classList.add("col-sm-6");
																					contactsColumn.setAttribute('id',`Contacts_${activationID}`);
																					contactsColumn.innerHTML=`<h4 style="text-align:center;">Contacts</h4>`;
																					
																					//display open by contact
																					{
																						var openBy = retrieved.client.openBy;
																						var currentUser = retrieved.users[openBy];
																						if(currentUser !== undefined)
																						{
																							var ActivationResponsePlan = retrieved.config.actionPlans[activationType].responsePlan;
																							var responsePlanUserTypes = retrieved.config.responsePlans[ActivationResponsePlan].userTypes;
																							var userTypeMatch = false;
																							for (var i =0; i< responsePlanUserTypes.length; i++)
																							{
																								if(currentUser.details.userType == responsePlanUserTypes[i])
																								{
																									userTypeMatch = true;
																								}
																							}
																							if(userTypeMatch)
																							{
																								var userMobile = currentUser.details.contact.mobile.detail;
																								var currentContact = document.createElement('div');
																								currentContact.classList.add('well');
																								currentContact.innerHTML = `<h4 style="text-align:center">${currentUser.details.name}</h4>`;
																								currentContact.innerHTML += `+${userMobile.substr(0,2)} (0)${userMobile.substr(2,2)} ${userMobile.substr(4,3)} ${userMobile.substr(7,4)}<br>`;
																								currentContact.innerHTML += `User Type: ${currentUser.details.userType}<br>`;
																								if(currentUser.details.userType == "User")
																								{
																									currentContact.innerHTML += `<button onclick="verifyPassword(${activationID},addResponseNote(${activationID},'Contacted ${currentUser.details.name}'));" type="button" class="btn btn-success">Contacted</button><button onclick="addResponseNote(${activationID},'Unable to contact ${currentUser.details.name}');" type="button" class="btn btn-danger">Contact Failed</button>`; 
																								}
																								else
																								{
																									currentContact.innerHTML += `<button onclick="addResponseNote(${activationID},'Contacted ${currentUser.details.name}');" type="button" class="btn btn-success">Contacted</button><button onclick="addResponseNote(${activationID},'Unable to contact ${currentUser.details.name}');" type="button" class="btn btn-danger">Contact Failed</button>`; 
																								}
																								contactsColumn.appendChild(currentContact);
																							}
																						}
																					}
																					//display other contacts
																					{
																						var clientUsers = retrieved.client.users;
																						for(var userID in clientUsers)
																						{
																							if(userID != openBy)
																							{
																								var currentUser = retrieved.users[userID];
																								var ActivationResponsePlan = retrieved.config.actionPlans[activationType].responsePlan;
																								var responsePlanUserTypes = retrieved.config.responsePlans[ActivationResponsePlan].userTypes;
																								var userTypeMatch = false;
																								for (var i =0; i< responsePlanUserTypes.length; i++)
																								{
																									if(currentUser.details.userType == responsePlanUserTypes[i])
																									{
																										userTypeMatch = true;
																									}
																								}
																								if(userTypeMatch)
																								{
																									var userMobile = currentUser.details.contact.mobile.detail;
																									var currentContact = document.createElement('div');
																									currentContact.classList.add('well');
																									currentContact.innerHTML = `<h4 style="text-align:center">${currentUser.details.name}</h4>`;
																									currentContact.innerHTML += `+${userMobile.substr(0,2)} (0)${userMobile.substr(2,2)} ${userMobile.substr(4,3)} ${userMobile.substr(7,4)}<br>`;
																									currentContact.innerHTML += `User Type: ${currentUser.details.userType}<br>`;
																									if(currentUser.details.userType == "User")
																								{
																									currentContact.innerHTML += `<button onclick="verifyPassword(${activationID},addResponseNote(${activationID},'Contacted ${currentUser.details.name}'));" type="button" class="btn btn-success">Contacted</button><button onclick="addResponseNote(${activationID},'Unable to contact ${currentUser.details.name}');" type="button" class="btn btn-danger">Contact Failed</button>`; 
																								}
																								else
																								{
																									currentContact.innerHTML += `<button onclick="addResponseNote(${activationID},'Contacted ${currentUser.details.name}');" type="button" class="btn btn-success">Contacted</button><button onclick="addResponseNote(${activationID},'Unable to contact ${currentUser.details.name}');" type="button" class="btn btn-danger">Contact Failed</button>`; 
																								}
																									contactsColumn.appendChild(currentContact);
																								}
																							}
																						}
																					}
																				}
																			}
																		}
																	}
																}
															}
														}
														function updateActivationSignals(retrieved)
														{
															var clientActivations = retrieved.client.activations;
															for(var activationType in clientActivations)
															{
																var currentActivationType = clientActivations[activationType];
																for(var activationID in currentActivationType)
																{
																	var currentActivation = currentActivationType[activationID];
																	if(currentActivation.isActive)
																	{
																		var signalContainer = document.getElementById(`signalContainer_${activationID}`);
																		
																		var assignedSignals = currentActivation.assignedSignals;
																		for(var i =0; i<assignedSignals.length; i++)
																		{
																			var signalID = assignedSignals[i];
																			var signalDetails = document.getElementById(`signal_${signalID}`);
																			var currentSignal = retrieved.signals[signalID];
																			if(signalDetails == null)
																			{
																				var signalDetails = document.createElement('div');
																				signalDetails.setAttribute('id',`signal_${signalID}`);
																				signalDetails.classList.add('well');
																				signalContainer.appendChild(signalDetails);
																				signalDetails.innerHTML = `<strong>${unixToTime(signalID)}</strong><br>`;
																				signalDetails.innerHTML += `<strong>${currentSignal.signal.eventName}</strong><br>`;
																				signalDetails.innerHTML += `Zone/User: ${currentSignal.signal.zone_user}<br>`;
																			}
																		}
																	}
																}
															}
														}
														function addResponseNote(activationID,content)
														{
															var content = content || "";
															var now = (Date.now())/1000;
															var user = retrieved.users['<?php echo $_SESSION["signedInUser"] ;?>'].details.name;
															var responseNotesColumn = document.getElementById(`responseNotes_${activationID}`);
															var buttons = responseNotesColumn.getElementsByTagName('button');
															var newResponseNote = document.createElement('div');
															newResponseNote.classList.add('well');
															newResponseNote.classList.add('activationResponse');
															newResponseNote.setAttribute('id',`response_${now}`);
															responseNotesColumn.insertBefore(newResponseNote,buttons[1]);
															newResponseNote.innerHTML = `<h4>${unixToTime(now)}:<br>${user}</h4>`;
															newResponseNote.innerHTML += `<textarea system_responseBy="<?php echo $_SESSION["signedInUser"]; ?>" class="form-control" name="responseContent_${now}" id="responseContent_${now}">${content}</textArea>`;
															return now;
														}
														function updateClient(what)
														{
															clientData = {};
															clientData.clientID = "<?php echo $_GET["clientID"] ;?>";
															clientData.signedInUser = "<?php echo $_SESSION["signedInUser"]; ?>";
															clientData.activations = {};
															var activations = document.getElementsByClassName('activationContent');
															for(var i =0; i < activations.length; i++)
															{
																var currentActivation = activations[i];
																var activationID = currentActivation.attributes.id.value.replace('activation_','');
																clientData.activations[activationID] = {};
																clientData.activations[activationID].activationID = activationID;
																clientData.activations[activationID].responses = {};
																var responses = activations[i].getElementsByClassName('activationResponse');
																for(var j = 0; j < responses.length; j++)
																{
																	var responseContent = responses[j].getElementsByTagName('textarea')[0];
																	var responseID = responseContent.attributes.id.value.replace('responseContent_','');
																	var content = responseContent.value;
																	var responseBy = responseContent.attributes.system_responseBy.value;
																	clientData.activations[activationID].responses[responseID] = {};
																	clientData.activations[activationID].responses[responseID].responseID = responseID;
																	clientData.activations[activationID].responses[responseID].responseBy = responseBy;
																	clientData.activations[activationID].responses[responseID].content = content;
																}
																
															}
															$.ajax({
																url:"update.php",
																async:true,
																type:"post",
																data:{token:"<?php echo $_SESSION["signedInUser"]?>",update:"client",what:what,data:clientData},
																dataType:"JSON",
																cache: false,
																success:function(msg){
																	if(msg.retrieved !== undefined)
																	{
																		if(msg.retrieved.activationCompleted !== undefined)
																		{
																			completeActivation(msg.retrieved.activationCompleted);
																		}
																	}
																}
															});
														}
														function completeActivation(activationID)
														{
															var activationLink = document.getElementById(`activationLink_${activationID}`);
															var activation = document.getElementById(`activation_${activationID}`);
															activation.parentElement.removeChild(activation);
															activationLink.parentElement.removeChild(activationLink);
															completedActivations[completedActivationCount] = activationID;
															completedActivationCount += 1;
															window.location.href = window.location.search;
														}
														function verifyPassword(activationID,responseNoteID)
														{
															var modal = document.getElementById('passwordVerificationModal');
															modal.style.display="block";
															modal.classList.add('in');
															document.getElementById('passwordVerification_responseNoteID').value = responseNoteID;
														}
														function testPasswordVerification(input)
														{
															var inputValue = input.value.toLowerCase().replace(/ /g,'');
															var notice = document.getElementById('passwordVerificationNotice');
															var modal = document.getElementById('passwordVerificationModal');
															var responseNoteID = document.getElementById('passwordVerification_responseNoteID').value;
															if(clientPasswords.OKPassword.toLowerCase().replace(/ /g,'') == inputValue)
															{
																notice.innerHTML = "";
																notice.classList.remove('alert-danger');
																notice.classList.remove('alert');
																modal.style.display="none";
																modal.classList.remove('in');
																document.getElementById(`responseContent_${responseNoteID}`).innerHTML += "\r\n\r\nOK Password Provided";
																window.alert('Contact has provided the OK Password, There is no need to dispatch Armed Response');
																input.value="";
																
															}
															else if(clientPasswords.emergencyPassword.toLowerCase().replace(/ /g,'') == inputValue)
															{
																notice.innerHTML = "";
																notice.classList.remove('alert-danger');
																notice.classList.remove('alert');
																modal.style.display="none";
																modal.classList.remove('in');
																document.getElementById(`responseContent_${responseNoteID}`).innerHTML += "\r\n\r\nEmergency Password Provided";
																window.alert('Contact has provided the Emergency Password, Please dispatch Armed Response');
																input.value="";
															}
															else
															{
																notice.classList.remove('alert-danger');
																notice.classList.remove('alert');
																notice.classList.add('alert-danger');
																notice.classList.add('alert');
																notice.innerHTML = `<strong>WARNING:</strong> The password you have entered does not match any password in the system.<br>Please ensure that the word is spelled correctly or ask the contact for the correct password`;
															}
														}
														function contactOffSite(input)
														{
															var modal = document.getElementById('passwordVerificationModal');
															var responseNoteID = document.getElementById('passwordVerification_responseNoteID').value;
															document.getElementById(`responseContent_${responseNoteID}`).innerHTML += "\r\n\r\nContact not on site";
															modal.style.display="none";
															modal.classList.remove('in');
														}
													</script>
												</div>
											</div>
										<?php
									}
								?>
								<div id="contact" class="tab-pane fade <?php if(!$activeActivationsFound){echo "active in";}?>" onclick="updateClientContact();" onkeyup="updateClientContact();">
									<h3>Contact Details</h3>
									<div class="container-fluid">
										<div class="col-sm-6">
											<label for="clientName">Client Name:</label>
											<input type="text" id="clientName" class="form-control" value="<?php echo $client -> clientName; ?>">
											<label for="clientAddress">Client Address:</label>
											<div id="clientAddress">
												Line 1:<input type="text" id="clientAddress_line1" class="form-control" value="<?php echo $client -> address -> line1; ?>">
												Line 2:<input type="text" id="clientAddress_line2" class="form-control" value="<?php echo $client -> address -> line2; ?>">
												Post Code:<input type="text" id="clientAddress_postCode" class="form-control" value="<?php echo $client -> address -> postCode; ?>">
											</div>
										</div>
										<div class="col-sm-6">
											<?php
												if(!$activeActivationsFound)
												{
													?>
														<div class="well" id="clientPasswords" title="Pause momentarily after typing the password to enable spellcheck to test the spelling.">
												<h4>Client Passwords</h4>
												<label for="OKPassword">OK Password:</label>
												<input spellcheck="true" type="text" id="OKPassword" class="form-control" value="<?php echo $client -> passwords -> OKPassword; ?>">
												<label for="emergencyPassword">Emergency Password:</label>
												<input spellcheck="true" type="text" id="emergencyPassword" class="form-control" value="<?php echo $client -> passwords -> emergencyPassword; ?>">
											</div>
													<?php
												}
											?>
										</div>
									</div>
									<script>
										function updateClientContact()
										{
											var clientData = {};
											clientData.clientID = "<?php echo $_GET["clientID"]; ?>";
											clientData.contact = {};
											clientData.contact.clientName = document.getElementById('clientName').value;
											clientData.contact.address = {};
											clientData.contact.address.line1 = document.getElementById('clientAddress_line1').value;
											clientData.contact.address.line2 = document.getElementById('clientAddress_line2').value;
											clientData.contact.address.postCode = document.getElementById('clientAddress_postCode').value;
											clientData.contact.passwords = {};
											clientData.contact.passwords.OKPassword = document.getElementById('OKPassword').value;
											clientData.contact.passwords.emergencyPassword = document.getElementById('emergencyPassword').value;
											$.ajax({
												url:"update.php",
												async:true,
												type:"post",
												data:{token:"<?php echo $_SESSION["signedInUser"]?>",update:"client",what:"contact",data:clientData},
												dataType:"JSON",
												cache: false,
												success:function(msg){
												}
											});
										}
									</script>
								</div>
								<div id="users" class="tab-pane fade" onclick="updateClientUsers();" onkeyup="updateClientUsers();">
									<h3>Users</h3>
									<div class="container-fluid">
										<div id="assignedUsers" class="col-sm-6" title="Hover over a user to edit, double-click a user to remove">
											<h3 style="text-align:center;">
												Assigned Users
											</h3>
											<?php
												$assignedUsers = array();
												$userNumbersInUse = array();
												foreach($clientUsers as $currentUser)
												{
													$userID = $currentUser -> userID;
													$assignedUsers[] = $userID;
													$userNumber = $currentUser -> userNumber;
													$userNumbersInUse[] = $userNumber;
													$userInfo = $usersClient -> getDoc($userID);
													$userName = $userInfo -> details -> name;
													?>
														<div ondblclick="removeClientUser(this);" class="well clientUsers" id="user_<?php echo $userID; ?>">
															<h3 style="text-align:center;"><?php echo $userName ?></h3>
															<div class="clientUsers_userNumber">
																<label for="user_<?php echo $userID; ?>_userNumber">User Number: </label>
																<input type="number" id="user_<?php echo $userID; ?>_userNumber" class="form-control" value="<?php echo $userNumber; ?>">
															</div>
														</div>
													<?php
												}
											?>
										</div>
										<div id="availableUsers" class="col-sm-6" title="Double-click a user to assign">
											<h3 style="text-align:center;">
												Available Users
											</h3>
											<?php
												foreach($usersClient -> getAllDocs() -> rows as $currentRow)
												{
													$userID = $currentRow -> id;
													if(!in_array($userID,$assignedUsers))
													{
														$currentUser = $usersClient -> getDoc($userID);
														$userName = $currentUser -> details -> name;
														?>
															<div ondblclick="addClientUser(this);" class="well clientUsers" id="user_<?php echo $userID; ?>">
																<h3 style="text-align:center;"><?php echo $userName ?></h3>
															</div>
														<?php
													}
												}
											?>
										</div>
									</div>
									<script>
										function removeClientUser(user)
										{
											var userNumber = user.getElementsByClassName('clientUsers_userNumber');
											user.removeChild(userNumber[0]);
											user.setAttribute('ondblclick',`addClientUser(this);`);
											var availableUsers = document.getElementById('availableUsers');
											user.parentElement.removeChild(user);
											availableUsers.appendChild(user);
											setTimeout(function(){updateClientUsers();},500);
										}
										function addClientUser(user)
										{
											user.parentElement.removeChild(user);
											var id = user.attributes.id.value;
											user.setAttribute('ondblclick',`removeClientUser(this);`);
											var userNumber = document.createElement('div');
											userNumber.classList.add('clientUsers_userNumber');
											userNumber.innerHTML += `<label for="${id}_userNumber">User Number: </label>`;
											userNumber.innerHTML += `<input type="number" id="${id}_userNumber" class="form-control">`;
											user.appendChild(userNumber);
											var assignedUsers = document.getElementById('assignedUsers');
											assignedUsers.appendChild(user);
											setTimeout(function(){updateClientUsers();},500);
										}
										function updateClientUsers()
										{
											var clientData = {};
											clientData.clientID = "<?php echo $_GET["clientID"]; ?>";
											clientData.assignedUsers = {};
											var assignedUsers = document.getElementById('assignedUsers').getElementsByClassName('clientUsers');
											for(var i = 0; i < assignedUsers.length; i++)
											{
												var userID = assignedUsers[i].attributes.id.value.replace('user_','');
												var userNumber = document.getElementById(`user_${userID}_userNumber`).value;
												clientData.assignedUsers[userID] = {};
												clientData.assignedUsers[userID].userID = userID;
												clientData.assignedUsers[userID].userNumber = userNumber;
											}
											$.ajax({
												url:"update.php",
												async:true,
												type:"post",
												data:{token:"<?php echo $_SESSION["signedInUser"]?>",update:"client",what:"users",data:clientData},
												dataType:"JSON",
												cache: false,
												success:function(msg){
												}
											});
										}
									</script>
								</div>
								<div id="zones" class="tab-pane fade" onclick="updateClientZones();" onkeyup="updateClientZones();">
									<h3>Zones</h3>
									<div class="container-fluid">
										<table id="zonesTable" class="table table-bordered table-striped">
											<thead>
												<th>Zone Number</th>
												<th>Zone Description</th>
												<th>Assigned Cameras</th>
												<th>Remove?</th>
											</thead>
											<tbody id="zonesTableContent">
												<?php
													foreach($client -> zones as $currentZone)
													{
														$zoneNumber = $currentZone -> zoneNumber;
														$zoneDescription = $currentZone -> zoneDescription;
														$assignedCameras = $currentZone -> assignedCameras;
														?>
															<tr title="Click a cell to edit">
																<td><input min="1" step="1" class="form-control" type="number" value="<?php echo $zoneNumber; ?>"></td>
																<td contenteditable="true"><?php echo $zoneDescription; ?></td>
																<td contenteditable="true"><?php echo $assignedCameras; ?></td>
																<td onclick="deleteRow(this);" style="text-align:center;color:red;font-size:2em;"><span class="glyphicon glyphicon-remove"></span></td>
															</tr>
														<?php
													}
												?>
											</tbody>
										</table>
										<button class="btn btn-success" onclick="addZone();">Add Zone</button>
										<script>
											function addZone()
											{
												var row = document.createElement('tr');
												row.innerHTML=`<td><input min="1" step="1" class="form-control" type="number"></td><td contenteditable="true"></td><td contenteditable="true"></td><td onclick="deleteRow(this);" style="text-align:center;color:red;font-size:2em;"><span class="glyphicon glyphicon-remove"></span></td>`;
												document.getElementById('zonesTableContent').appendChild(row);
											}
											function deleteRow(close)
											{
												var row = close.parentElement;
												row.parentElement.removeChild(row);
											}
											function updateClientZones()
											{
												var clientData = {};
												clientData.clientID = "<?php echo $_GET["clientID"]; ?>";
												clientData.zones = {};
												var zoneRows = document.getElementById('zonesTableContent').getElementsByTagName('tr');
												for(var i = 0; i < zoneRows.length; i++)
												{
													var currentRow = zoneRows[i];
													var zoneNumber = currentRow.getElementsByTagName('td')[0].getElementsByTagName('input')[0].value;
													clientData.zones[zoneNumber] = {};
													clientData.zones[zoneNumber].zoneNumber = zoneNumber;
													clientData.zones[zoneNumber].zoneDescription = currentRow.getElementsByTagName('td')[1].innerText;
													clientData.zones[zoneNumber].assignedCameras = currentRow.getElementsByTagName('td')[2].innerText;
												}
												$.ajax({
													url:"update.php",
													async:true,
													type:"post",
													data:{token:"<?php echo $_SESSION["signedInUser"]?>",update:"client",what:"zones",data:clientData},
													dataType:"JSON",
													cache: false,
													success:function(msg){
													}
												});
											}
										</script>
									</div>
								</div>
								<div id="tests" class="tab-pane fade">
									<h3>Tests</h3>
									<div class="container-fluid">
										<label for="testModeActive">Test Mode Enabled:</label>
										<input onclick="testModeToggle(this);" type="checkbox" id="testModeActive" class="form-control" <?php if($client -> testModeActive){echo "checked";}?>><br>
										<div id="testModeNotification">
											
										</div>
									</div>
									<script>
										function testModeToggle(element)
										{
											var clientData = {};
											clientData.clientID = "<?php echo $_GET["clientID"]; ?>";
											clientData.testModeActive = element.checked;
											$.ajax({
												url:"update.php",
												async:false,
												type:"post",
												data:{token:"<?php echo $_SESSION["signedInUser"]?>",update:"client",what:"testMode",data:clientData},
												dataType:"JSON",
												cache: false,
												success:function(msg){
													var notification = document.createElement('div');
													notification.classList.add('alert');
													notification.classList.add('alert-success');
													if(msg.result == "enabled")
													{
														notification.innerHTML = `<strong>Test Mode Enabled:</strong> Test Mode has been successfully enabled`;
													}
													else
													{
														notification.innerHTML = `<strong>Test Mode Disabled:</strong> Test Mode has been successfully disabled`;
													}
													var notificationContainer = document.getElementById('testModeNotification');
													notificationContainer.appendChild(notification);
													setTimeout(function(){notificationContainer.removeChild(notification);window.location.href=window.location.href;},3000);
												}
											});
										}
									</script>
								</div>
							</div>
						</div>
					</div>
				<?php
			}
			else
			{
				unauthorised();
			}
		}
		function userList()
		{
			global $usersClient, $userSecurity;
			if($userSecurity -> users -> enable)
			{
				?>
					<title>COREmonitor || Users</title>
					<div class="container">
						<h2>Users</h2>
						<button style="float:right;" class="btn btn-success" onclick="window.location.href='?page=users&userID=new';">Add New User</button><br>
						<br>
						<input class="form-control" id="userSearch" type="text" placeholder="Search..">
						<br>
						<table class="table table-bordered table-striped">
							<thead>
								<tr>
									<th>User Name</th>
									<th>User Type</th>
									<th>Mobile</th>
									<th>Email</th>
								</tr>
							</thead>
							<tbody id="userTable">
								<?php
									$allUsers = $usersClient -> getAllDocs();
									$users_sorted = array();
									$userNames = array();
									foreach($allUsers -> rows as $currentRow)
									{
										$userID = $currentRow -> id;
										$currentUser = $usersClient -> getDoc($userID);
										$userName = $currentUser -> details -> name;
										$userNames[] = "$userName $userID";
										$users_sorted["$userName $userID"] = $currentUser;
										
									}
									sort($userNames);
									//loop through clientNames
									foreach($userNames as $currentUserName)
									{
										$currentUser = $users_sorted[$currentUserName];
										$userID = $currentUser -> _id;
										$userName = $currentUser -> details -> name;
										$userType = $currentUser -> details -> userType;
										$userMobile = "+" . substr($currentUser -> details -> contact -> mobile -> detail,0,2) . " (0)"; 
										$userMobile .= substr($currentUser -> details -> contact -> mobile -> detail,2,2) . " ";
										$userMobile .= substr($currentUser -> details -> contact -> mobile -> detail,4,3) . " ";
										$userMobile .= substr($currentUser -> details -> contact -> mobile -> detail,7,4);
										$userEmail = $currentUser -> details -> contact -> email -> detail;
										?>
										<tr>
											<td style="cursor:<?php if($userSecurity -> users -> edit){echo "pointer";}else{echo "not-allowed";} ?>;" <?php if($userSecurity -> users -> edit){echo"onclick=\"window.location.href='?page=users&userID=$userID'\"";} ?>><?php echo $userName; ?></td>
											<td style="cursor:<?php if($userSecurity -> users -> edit){echo "pointer";}else{echo "not-allowed";} ?>;" <?php if($userSecurity -> users -> edit){echo"onclick=\"window.location.href='?page=users&userID=$userID'\"";} ?>><?php echo $userType; ?></td>
											<td style="cursor:<?php if($userSecurity -> users -> edit){echo "pointer";}else{echo "not-allowed";} ?>;" <?php if($userSecurity -> users -> edit){echo"onclick=\"window.location.href='?page=users&userID=$userID'\"";} ?>><?php echo $userMobile; ?></td>
											<td style="cursor:<?php if($userSecurity -> users -> edit){echo "pointer";}else{echo "not-allowed";} ?>;" <?php if($userSecurity -> users -> edit){echo"onclick=\"window.location.href='?page=users&userID=$userID'\"";} ?>><?php echo $userEmail; ?></td>
										</tr>
										<?php
									}
								?>
							</tbody>
						</table>
						<script>
							$(document).ready(function()
							{
								$("#userSearch").on("keyup", function()
								{
									var value = $(this).val().toLowerCase();
									$("#userTable tr").filter(function()
									{
										$(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
									});
								});
							});
							</script>
					</div>
				<?php
			}
			else
			{
				unauthorsed();
			}
		}
		function editUser()
		{
			echo "<title>COREmonitor || Edit User</title>";
			global $userSecurity, $usersClient, $configClient;
			if(($userSecurity -> users -> edit))
			{
				if($_GET["userID"] == "new")
				{
					$doc = new stdClass();
					$doc -> details -> name = "New User";
					$_GET["userID"] = $usersClient -> storeDoc($doc) -> id;
					echo "<script>window.location.search=\"?page=" . $_GET["page"] . "&userID=" . $_GET["userID"] . "\";</script>";
				}
				try
				{
					$user = $usersClient -> getDoc($_GET["userID"]);
				}
				catch (Exception $e)
				{
					echo "<script>window.alert('",  $e->getMessage(), "'); window.location.href='?page=users';</script>";
				}
				$userTypes = $configClient -> getDoc("userTypes") -> types;
				?>
					<div class="container-fluid">
						<h1><?php echo $user -> details -> name;?></h1>
						<div class="col-sm-3">
							<ul class="nav nav-pills nav-stacked">
								<li class="active"><a data-toggle="pill" href="#details">User Details</a></li>
								<?php
									if($userSecurity -> users -> editPlatform)
									{
										?>
											<li><a data-toggle="pill" href="#platform">Platform Settings</a></li>
										<?php
									}
								?>
							</ul>
						</div>
						<div class="col-sm-9">
							<div class="tab-content" onclick="updateUser();" onkeyup="updateUser();">
								<div id="details" class="tab-pane fade active in">
									<h3>User Details</h3>
									<div class="container-fluid">
										<div class="col-sm-6">
											<div class="form-group">
												<label for="fullName">Full Name:</label>
												<input required type="text" class="form-control" id="fullName" placeholder="John Doe" value="<?php echo $user -> details -> name; ?>">
												<label for="userType">User Type:</label>
												<select id="userType" class="form-control">
													<?php
														foreach($userTypes as $currentUserType)
														{
															echo "<option";
															if($currentUserType == $user -> details -> userType)
															{
																echo " selected";
															}
															echo ">$currentUserType</option>";
														}
													?>
												</select>
												
											</div>
										</div>
										<div class="col-sm-6">
											<div class="form-group">
												<label for="email">Email Address:</label>
												<input required type="email" class="form-control" id="email" placeholder="John.Doe@example.com" value="<?php echo $user -> details -> contact -> email -> detail; ?>">
												<label for="email_respond">Send Notifications via Email?:</label>
												<input type="checkbox" id="email_respond" <?php if($user -> details -> contact -> email -> respond){ echo "checked"; } ?>>
												<div id="mobileContact">
													<label for="mobile">Mobile:</label><br>
													+ <input required style="width:50px;display:inline-block" minlength="2" maxlength="2" type="text" class="form-control" placeholder="27"  value="<?php echo substr($user -> details -> contact -> mobile -> detail,0,2); ?>">
													(0) <input required style="width:50px;display:inline-block" minlength="2" maxlength="2" type="text" class="form-control" placeholder="82"  value="<?php echo substr($user -> details -> contact -> mobile -> detail,2,2); ?>">
													<input required style="width:50px;display:inline-block" minlength="3" maxlength="3" type="text" class="form-control" placeholder="123"  value="<?php echo substr($user -> details -> contact -> mobile -> detail,4,3); ?>">
													- <input required style="width:60px;display:inline-block" minlength="4" maxlength="4" type="text" class="form-control" placeholder="4567"  value="<?php echo substr($user -> details -> contact -> mobile -> detail,7,4); ?>"><br>
												</div>
												<label for="email_respond">Send Notifications via SMS?:</label>
												<input type="checkbox" id="mobile_respond" <?php if($user -> details -> contact -> mobile -> respond){ echo "checked"; } ?>>
												<div id="landLineContact">
													<label for="landLine">Land Line:</label><br>
													+ <input style="width:50px;display:inline-block" minlength="2" maxlength="2" type="text" class="form-control" placeholder="27"  value="<?php echo substr($user -> details -> contact -> landLine -> detail,0,2); ?>">
													(0) <input style="width:50px;display:inline-block" minlength="2" maxlength="2" type="text" class="form-control" placeholder="11"  value="<?php echo substr($user -> details -> contact -> landLine -> detail,2,2); ?>">
													<input style="width:50px;display:inline-block" minlength="3" maxlength="3" type="text" class="form-control" placeholder="123"  value="<?php echo substr($user -> details -> contact -> landLine -> detail,4,3); ?>">
													- <input style="width:60px;display:inline-block" minlength="4" maxlength="4" type="text" class="form-control" placeholder="4567"  value="<?php echo substr($user -> details -> contact -> landLine -> detail,7,4); ?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php
									if($userSecurity -> users -> editPlatform)
									{
										?>
											<div id="platform" class="tab-pane fade">
												<h3>Platform Settings</h3>
												<div class="container-fluid">
													<div class="col-sm-6">
														<div class="form-group">
															<label for="enablePlatformCheckbox">Enable Platform Access:</label>
															<input onchange="testPlatformAccess();" type="checkbox" class="form-control" id="enablePlatformCheckbox" <?php if($user -> platform -> allow){echo "checked";} ?>>
														</div>
													</div>
													<div class="col-sm-6"></div>
												</div>
												<div class="container-fluid" id="enablePlatform" style="display:none;">
													<div class="container-fluid">
														<div class="col-sm-6">
															<div class="form-group">
																<label for="password">Password:</label>
																<input onkeyup="platformPasswordChange();" type="password" class="form-control" id="password" placeholder="Password Unchanged">
																<span id="passwordStrength"></span>
															</div>
														</div>
														<div calss="col-sm-6"></div>
													</div>
													<div class="container-fluid" id="securitySettings">
														<h3 style="text-align:center;">Security Settings</h3>
														<ul class="nav nav-tabs">
															<li class="active"><a data-toggle="tab" href="#security_dashboard">Dashboard</a></li>
															<li><a data-toggle="tab" href="#security_monitoring">Monitoring</a></li>
															<li><a data-toggle="tab" href="#security_clients">Clients</a></li>
															<li><a data-toggle="tab" href="#security_users">Users</a></li>
															<li><a data-toggle="tab" href="#security_reports">Reports</a></li>
														</ul>
														<div class="tab-content">
															<div id="security_dashboard" class="tab-pane fade in active" onclick="testDashboardEnable();">
																<h3>Dashboard Settings</h3>
																<span>Enable: <input type="checkbox" name="dashboard_enable" id ="dashboard_enable" <?php if($user -> platform ->security -> dashboard -> enable){ echo "checked";} ?>></span></br>
																<span>Display Activations: <input type="checkbox" name="dashboard_activation" id ="dashboard_activations" <?php if($user -> platform ->security -> dashboard -> activations){ echo "checked";} ?>></span></br>
																<span>(Future Development) Display Online Devices: <input disabled type="checkbox" name="dashboard_onlineDevices" id ="dashboard_onlineDevices" <?php if($user -> platform ->security -> dashboard -> onlineDevices){ echo "checked";} ?>></span></br>
																<span>Display Open Clients: <input type="checkbox" name="dashboard_openClients" id ="dashboard_openClients" <?php if($user -> platform ->security -> dashboard -> openClients){ echo "checked";} ?>></span></br>
																<script>
																	function testDashboardEnable()
																	{
																		var inputs = document.getElementById('security_dashboard').getElementsByTagName('input');
																		for(var i = 0; i < inputs.length; i++)
																		{
																			if(inputs[i].checked)
																			{
																				document.getElementById('dashboard_enable').checked = true;
																			}
																		}
																	}
																</script>
															</div>
															<div id="security_monitoring" class="tab-pane fade" onclick="testMonitoringEnable();">
																<h3>Monitoring</h3>
																<span>Enable: <input type="checkbox" name="monitoring_enable" id ="monitoring_enable" <?php if($user -> platform ->security -> monitoring -> enable){ echo "checked";} ?>></span></br>
																<span>Signals: <input type="checkbox" name="monitoring_signals" id ="monitoring_signals" <?php if($user -> platform ->security -> monitoring -> signals){ echo "checked";} ?>></span></br>
																<span>(Future Development) Activations: <input disabled type="checkbox" name="monitoring_activations" id ="monitoring_activations" <?php if($user -> platform ->security -> monitoring -> activations){ echo "checked";} ?>></span></br>
																<script>
																	function testMonitoringEnable()
																	{
																		var inputs = document.getElementById('security_monitoring').getElementsByTagName('input');
																		for(var i = 0; i < inputs.length; i++)
																		{
																			if(inputs[i].checked)
																			{
																				document.getElementById('monitoring_enable').checked = true;
																			}
																		}
																	}
																</script>
															</div>
															<div id="security_clients" class="tab-pane fade" onclick="testClientsEnable();">
																<h3>Clients</h3>
																<span>Enable: <input type="checkbox" name="clients_enable" id ="clients_enable" <?php if($user -> platform ->security -> clients -> enable){ echo "checked";} ?>></span></br>
																<span>Edit Clients: <input type="checkbox" name="clients_edit" id ="clients_edit" <?php if($user -> platform ->security -> clients -> edit){ echo "checked";} ?>></span></br>
																<span id="security_clients_activations" onclick="testClients_activationsEnable();">
																	<span><strong>Activations</strong> &emsp; Enable: <input disabled type="checkbox" name="clients_activations_enable" id ="clients_activations_enable" <?php if($user -> platform ->security -> clients -> activations -> enable){ echo "checked";} ?>></span>(Not Yet Enforced)</br>
																	<span><strong>Activations</strong> &emsp; Edit: <input disabled type="checkbox" name="clients_activations_edit" id ="clients_activations_edit" <?php if($user -> platform ->security -> clients -> activations -> edit){ echo "checked";} ?>></span>(Not Yet Enforced)</br>
																	<span><strong>Activations</strong> &emsp; Add Response Notes: <input disabled type="checkbox" name="clients_activations_addResponseNotes" id ="clients_activations_addResponseNotes" <?php if($user -> platform ->security -> clients -> activations -> addResponseNotes){ echo "checked";} ?>></span>(Not Yet Enforced)</br>
																	<span><strong>Activations</strong> &emsp; Edit Response Notes: <input disabled type="checkbox" name="clients_activations_editResponseNotes" id ="clients_activations_editResponseNotes" <?php if($user -> platform ->security -> clients -> activations -> editResponseNotes){ echo "checked";} ?>></span>(Not Yet Enforced)</br>
																	<span><strong>Activations</strong> &emsp; Remove Response Notes: <input disabled type="checkbox" name="clients_activations_removeResponseNotes" id ="clients_activations_removeResponseNotes" <?php if($user -> platform ->security -> clients -> activations -> removeResponseNotes){ echo "checked";} ?>></span>(Future Development)</br>
																</span>
																<script>
																	function testClientsEnable()
																	{
																		var inputs = document.getElementById('security_clients').getElementsByTagName('input');
																		for(var i = 0; i < inputs.length; i++)
																		{
																			if(inputs[i].checked)
																			{
																				document.getElementById('clients_enable').checked = true;
																			}
																		}
																	}
																	function testClients_activationsEnable()
																	{
																		var inputs = document.getElementById('security_clients_activations').getElementsByTagName('input');
																		for(var i = 0; i < inputs.length; i++)
																		{
																			if(inputs[i].checked)
																			{
																				document.getElementById('clients_activations_enable').checked = true;
																			}
																		}
																	}
																</script>
															</div>
															<div id="security_users" class="tab-pane fade" onclick="testUsersEnable();">
																<h3>Users</h3>
																<span>Enable: <input type="checkbox" name="users_enable" id ="users_enable" <?php if($user -> platform ->security -> users -> enable){ echo "checked";} ?>></span></br>
																<span>Edit Users: <input type="checkbox" name="users_edit" id ="users_edit" <?php if($user -> platform ->security -> users -> edit){ echo "checked";} ?>></span></br>
																<span>Edit Platform Settings: <input type="checkbox" name="users_editPlatform" id ="users_editPlatform" <?php if($user -> platform ->security -> users -> editPlatform){ echo "checked";} ?>></span></br>
																<script>
																	function testUsersEnable()
																	{
																		var inputs = document.getElementById('security_users').getElementsByTagName('input');
																		for(var i = 0; i < inputs.length; i++)
																		{
																			if(inputs[i].checked)
																			{
																				document.getElementById('users_enable').checked = true;
																			}
																		}
																	}
																</script>
															</div>
															<div id="security_reports" class="tab-pane fade" onclick="testReportsEnable();">
																<h3>Reports</h3>
																<span>Enable: <input type="checkbox" name="reports_enable" id ="reports_enable" <?php if($user -> platform ->security -> reports -> enable){ echo "checked";} ?>></span></br>
																<script>
																	function testReportsEnable()
																	{
																		var inputs = document.getElementById('security_reports').getElementsByTagName('input');
																		for(var i = 0; i < inputs.length; i++)
																		{
																			if(inputs[i].checked)
																			{
																				document.getElementById('reports_enable').checked = true;
																			}
																		}
																	}
																</script>
															</div>
														</div>
													</div>
												</div>
												<script>
													passwordChange = false;
													testPlatformAccess();
													function testPlatformAccess()
													{
														var enablePlatformCheckbox = document.getElementById('enablePlatformCheckbox');
														if(enablePlatformCheckbox.checked)
														{
															document.getElementById('enablePlatform').style.display="block";
														}
														else
														{
															document.getElementById('enablePlatform').style.display="none";
														}
													}
													function platformPasswordChange()
													{
														passwordChange = true;
														hsimp({
															options: {
																calculationsPerSecond: 1e10, // 10 billion,
																good: 31557600e3, // 1,000 years
																ok: 31557600 // 1 year
															},
															outputTime: function (time, input) {
															},
															outputChecks: function (checks, input) {
																document.getElementById("passwordStrength").innerHTML = "";
																for(i=0;i<checks.length;i++)
																{
																	if((checks[i].level == "warning") || (checks[i].level == "insecure") ||(checks[i].level == "notice"))
																	{
																		document.getElementById("passwordStrength").innerHTML += `<div class="alert alert-danger"><strong>${checks[i].level}:</strong> ${checks[i].message}</div>`;
																	}
																	else if((checks[i].level == "achievement"))
																	{
																		document.getElementById("passwordStrength").innerHTML += `<div class="alert alert-success"><strong>${checks[i].level}:</strong> ${checks[i].message}</div>`;
																	}
																	else
																	{
																		document.getElementById("passwordStrength").innerHTML += `${checks[i].level}: ${checks[i].message}<br>`;
																	}
																}
															}
														}, document.getElementById("password"));
													}
												</script>
											</div>
										<?php
									}
									?>
							</div>
							<script>
								function updateUser()
								{
									var userData = {};
									userData.userID = "<?php echo $_GET["userID"] ?>";
									userData.details= {};
									userData.details.name = document.getElementById('fullName').value;
									userData.details.userType = document.getElementById('userType').value;
									userData.details.contact = {};
									userData.details.contact.email = {};
									userData.details.contact.mobile = {};
									userData.details.contact.landLine = {};
									userData.details.contact.email.detail = document.getElementById('email').value;
									userData.details.contact.email.respond = document.getElementById('email_respond').checked;
									userData.details.contact.mobile.detail = "";
									var mobileInputs = document.getElementById('mobileContact').getElementsByTagName('input');
									for(var i = 0; i < mobileInputs.length; i ++)
									{
										userData.details.contact.mobile.detail += mobileInputs[i].value;
									}
									userData.details.contact.mobile.respond = document.getElementById('mobile_respond').checked;
									userData.details.contact.landLine.detail = "";
									var landLineInputs = document.getElementById('landLineContact').getElementsByTagName('input');
									for(var i = 0; i < landLineInputs.length; i ++)
									{
										userData.details.contact.landLine.detail += landLineInputs[i].value;
									}
									var editPlatform = false;
									<?php
										if($userSecurity -> users -> editPlatform)
										{
											?>
												var editPlatform = true;
												userData.platform = {};
												userData.platform.allow = document.getElementById('enablePlatformCheckbox').checked;
												userData.platform.passwordChange = passwordChange;
												if(passwordChange)
												{
													userData.platform.password = document.getElementById('password').value;
												}
												userData.platform.security = {};
												userData.platform.security.dashboard = {};
												userData.platform.security.dashboard.enable = document.getElementById('dashboard_enable').checked;
												userData.platform.security.dashboard.activations = document.getElementById('dashboard_activations').checked;
												userData.platform.security.dashboard.onlineDevices = document.getElementById('dashboard_onlineDevices').checked;
												userData.platform.security.dashboard.openClients = document.getElementById('dashboard_openClients').checked;
												userData.platform.security.monitoring = {};
												userData.platform.security.monitoring.enable = document.getElementById('monitoring_enable').checked;
												userData.platform.security.monitoring.signals = document.getElementById('monitoring_signals').checked;
												userData.platform.security.monitoring.activations = document.getElementById('monitoring_activations').checked;
												userData.platform.security.clients = {};
												userData.platform.security.clients.enable = document.getElementById('clients_enable').checked;
												userData.platform.security.clients.edit = document.getElementById('clients_edit').checked;
												userData.platform.security.clients.activations = {};
												userData.platform.security.clients.activations.enable = document.getElementById('clients_activations_enable').checked;
												userData.platform.security.clients.activations.edit = document.getElementById('clients_activations_edit').checked;
												userData.platform.security.clients.activations.addResponseNotes = document.getElementById('clients_activations_addResponseNotes').checked;
												userData.platform.security.clients.activations.editResponseNotes = document.getElementById('clients_activations_editResponseNotes').checked;
												userData.platform.security.clients.activations.removeResponseNotes = document.getElementById('clients_activations_removeResponseNotes').checked;
												userData.platform.security.users = {};
												userData.platform.security.users.enable = document.getElementById('users_enable').checked;
												userData.platform.security.users.edit = document.getElementById('users_edit').checked;
												userData.platform.security.users.editPlatform = document.getElementById('users_editPlatform').checked;
												userData.platform.security.reports = {};
												userData.platform.security.reports.enable = document.getElementById('reports_enable').checked;
											<?php
										}
									?>
										$.ajax({
										url:"update.php",
										async:true,
										type:"post",
										data:{token:"<?php echo $_SESSION["signedInUser"]?>",update:"user",data:userData,editPlatform:editPlatform},
										dataType:"JSON",
										cache: false,
										success:function(msg){
										}
									});
								}
							</script>
						</div>
					</div>
					
				<?php
			}
			else
			{
				unauthorised();
			}
		}
		function signalsPage()
		{
			?>
				<div class="container-fluid">
					<h1>Signals</h1>
					<div class="container-fluid" id="signalsBuffer">
						
					</div>
					<script>
						function updateSignals(retrieved)
						{
							var signalsBuffer = document.getElementById('signalsBuffer');
							var signals_sorted = Array();
							var i = 0;
							for(var signalID in retrieved.signals)
							{
								signals_sorted[i] = signalID;
								i += 1;
							}
							signals_sorted.sort();
							for(var i=0;i<signals_sorted.length;i++)
							{
								
								signalID=signals_sorted[i];
								var currentSignal = retrieved.signals[signalID];
								var dateTime = currentSignal.dateTime;
								var rawData = currentSignal.rawData;
								var clientID = currentSignal.signal.clientID;
								var eventName = currentSignal.signal.eventName
								var fromIP = currentSignal.signal.fromIP;
								var zone_user = currentSignal.signal.zone_user;
								var eventType = currentSignal.signal.eventType;
								var eventQualifier = currentSignal.signal.qualifier;
								var clientName = retrieved.clients[clientID].clientName;
								if(document.getElementById(`signal_${signalID}`) == null)
								{
									var signalWell = document.createElement('div');
									signalWell.setAttribute('id',`signal_${signalID}`);
									signalWell.classList.add('well');
									signalWell.classList.add('signalMonitor-signal');
									signalWell.innerHTML = `<h4 style="text-align:center;">${dateTime} - ${clientName}</h4>`;
									signalWell.innerHTML += `<div class="containerFluid signalMonitor-signalDetails">
									<table class="table"><tbody><tr><td><strong><td><strong>Event:</strong> ${eventName}</td><td><strong>From IP:</strong> ${fromIP}</td><td><strong>raw:</strong> ${rawData}</td></tr><tr><td><strong><td><strong>Event type:</strong> ${eventType}</td><td><strong>Zone / User: </strong> ${zone_user}</td><td><strong>Qualifier:</strong> ${eventQualifier}</td></tr></tbody></table></div>`;
									document.getElementById('signalsBuffer').prepend(signalWell);
								}
							}
						}
					</script>
				</div>
			<?php
		}
		function activationsPage()
		{
			?>
				<div class="container-fluid">
					<h1>Activations</h1>
					<div id="activationsContent"></div>
				</div>
			<?php
		}
	}
	//functions
	{
		function supercrypt($string)
		{
			$forward = str_split($string);
			$inverse = strrev($string);
			$first = crypt($string,"!Nt3gr@t3dC0r3Gr0up20155102pu0rG3r0Cd3t@rg3tN!");
			$second = crypt($inverse,"!Nt3gr@t3dC0r3Gr0up20155102pu0rG3r0Cd3t@rg3tN!");
			$third = crypt($first,$second);
			$fourth = crypt($second,$first);
			$fifth = crypt($first,$inverse);
			$sixth = crypt($second,$string);
			return($first.$second.$third.$fourth.$fifth.$sixth);
		}
		function unauthorised()
		{
			?>
				<script>
					window.alert('You are not Authorized to view this page');
					window.location.href="/";
				</script>
			<?php
		}
	}
	function store($print)
	{
	    fopen('print.txt', 'w');
	    file_put_contents('print.txt',print_r($print, true));
	}
?>