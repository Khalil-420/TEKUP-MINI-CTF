const express = require('express');
const qs = require('querystring');
const app = express();
const userRoutes = express.Router();

let User = require('../models/User');

userRoutes.route('/lookup').get(function(req, res) {
	let username = req.query.username;
	console.log("request " + JSON.stringify(username));
	if (typeof username !== 'undefined' && username != "") {
		query = { $where: `this.username == '${username}'` }
		//Simple injection: pass in "' || '2'=='2" (without double quotes)
		// This will return all records
		//
		// JS injection is also possible here, because the where clause evaluates a JS expression

		User.find(query, function (err, users) {
			if (err) {
				console.log(err);
				res.json(err);
			} else {
				res.render('userlookup', { title: 'User Lookup', users: users });
			}
		});
	}
	else {
		res.render('userlookup', { title: 'User Lookup', users:[]});
	}	
});

/** Allow a similar query using POST and JSON
  * Similar to above, inject data like
  * {"username":"' || '2'=='2"}

  Sample default CURL request::
	curl -X POST http://localhost:4000/user/lookup -H 'Content-Type: application/json' -d '{"username": "guest"}'
  And Injection (need to escape our injected single quotes for CURL only):
	curl -X POST http://localhost:4000/user/lookup -H 'Content-Type: application/json' -d '{"username": "guest'\'' || '\''2'\''=='\''2"}'
  */
userRoutes.route('/lookup').post(function(req, res) {
	let username = req.body.username;
	console.log("request " + JSON.stringify(username));
	if (typeof username !== 'undefined') {
		query = { $where: `this.username == '${username}'` }
		
		User.find(query, function (err, users) {
			if (err) {
				console.log(err);
				res.json(err);
			} else {
				console.log("Data Retrieved: " + users);
				res.json({users});
			}
		});
	}
	else {
		res.json({});
	}	
});


userRoutes.route('/login').get(function(req, res) {
	res.render('userlogin', { title: 'User Login', role: "None"});
});

userRoutes.route('/login').post(function(req, res) {
	let uname = req.body.username;
	let pass = req.body.password;
	
	let query = { 
		username: uname,
		password: pass 
	}

	
	User.find(query, function (err, user) {
		if (err) {
			console.log(err);
			res.json(err);
		} else {
			console.log(user);
			if (user.length >= 1 && user[0].role != "admin" ) {
				var msg = "Logged in as user " + user[0].username + " with role " + user[0].role;
				res.json({role: user[0].role, username: user[0].username, msg: msg });
			}
			else if (user.length >= 1 && user[0].role == "admin"){
				res.json({role: user[0].role, msg: "Good job you hacked into the IDF website here is the flag: Securinets{IDF_IS_TH3_BIGG3ST_LI3}" });
			} 
			else
				res.json({role: "invalid", msg: "Invalid username or password."});
		}
	});

});



module.exports = userRoutes;
