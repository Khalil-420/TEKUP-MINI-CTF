from flask import Flask, render_template, request,redirect,session,url_for
import sqlite3

app = Flask(__name__)


app.secret_key = 'a_very_secret_key_must_be_here'
conn = sqlite3.connect('sqli0.db')
cursor = conn.cursor()
cursor.execute('''
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL,
        password TEXT NOT NULL
    )
''')
cursor.execute("INSERT INTO users (username, password) VALUES ('A_VERy_RANDOM_USERNAME_here', '4_VERY_SECRET_PASSW0RD_NONE_CAN_GUESS!!!')")
conn.commit()
conn.close()

conn = sqlite3.connect('sqli0.db')
cursor = conn.cursor()
query = "select sqlite_version()"
cursor.execute(query)
version = cursor.fetchone()
conn.close()

@app.route('/')
def index():
    return render_template('login.html')
@app.route('/login', methods=['POST'])
def login():
    username = request.form['username']
    password = request.form['password']
    if ' ' in username or ' ' in password:
        return "username and password cannot contain spaces!"
    if 'OR' in username.upper() or 'OR' in password.upper():
        return "\"OR\" is not allowed!"
    query = f"SELECT * FROM users WHERE username='{username}' AND password='{password}'"
    
    conn = sqlite3.connect('sqli0.db')
    cursor = conn.cursor()
    try:
        cursor.execute(query)
        user = cursor.fetchone()
        conn.close()

        if user:
            session['connected'] = True
            return redirect(url_for('home'))
        else:
            return redirect(url_for('error', error_message="Login failed"))
    except Exception as e:
        return redirect(url_for('error', error_message=str(e)+"  sqlite"+str(version[0])))

@app.route('/home')
def home():
    if session.get('connected'):
        return render_template('home.html')
    else:
        return redirect(url_for('error', error_message="You are not authorized to access this page."))

@app.route('/error')
def error():
    error_message = request.args.get('error_message', 'An error occurred.')
    return render_template('error.html', error_message=error_message)

if __name__ == '__main__':
    app.run()
