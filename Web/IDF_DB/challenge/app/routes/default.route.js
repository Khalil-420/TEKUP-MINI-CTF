const express = require('express');
const app = express();
const defaultRoutes = express.Router();

let User = require('../models/User');

defaultRoutes.route('/').get(function(req, res) {
    // Delete all existing users first
    User.deleteMany({}, function(err) {
        if (err) {
            console.error("Error deleting existing users:", err);
            return res.status(500).send("Error deleting existing users");
        }

        // Define users to add
        let users = [
		{
			username: "avichay",
			password: "2TR6uTRAuMUr5vARs9fYgdqY",
			first_name: "Avichay",
			last_name: "adraee",
			role: "admin",
			email: "avichay@test.com"
		},
		{
			username: "guest",
			password: "password",
			first_name: "",
			last_name: "",
			role: "guest",
			email: "guest@test.com"
		},
		{
			username: "leah",
			password: "abc123",
			first_name: "Leah",
			last_name: "Ghost",
			role: "user",
			email: "leah@test.com"
		},
		{
			username: "moshe",
			password: "SuPeRsEcR3T",
			first_name: "Moshe",
			last_name: "Smoth",
			role: "user",
			email: "jsmith@gmail.com"
		},
		{
			username: "itzhak",
			password: "L1g7tM3Up!",
			first_name: "Itzhak",
			last_name: "Jorgen",
			role: "user",
			email: "prismman@yahoo.com"
		}
	];

        let usersAddedCount = 0;

        users.forEach(userObj => {
            let user = new User(userObj);
            user.save()
                .then(item => {
                    console.log("Added user: " + item.username);
                    usersAddedCount++;
                    if (usersAddedCount === users.length) {
                        // After adding all users, redirect to the '/' route
                         res.render('index', { title: 'Home', message: 'A Vulnerable Node & Mongo App' });
                    }
                })
                .catch(err => {
                    console.error("Error adding user:", err);
                    res.status(500).send("Error adding user: " + userObj.username);
                });
        });
    });
});

module.exports = defaultRoutes;




















