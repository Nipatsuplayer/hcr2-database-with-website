<h1 align="center" id="title">HCR2 ADVENTURE DATABASE + WEBSITE</h1>

<p align="center"><img src="https://socialify.git.ci/Nipatsuplayer/hcr2-database-with-website/image?font=Bitter&amp;forks=1&amp;issues=1&amp;language=1&amp;name=1&amp;owner=1&amp;pattern=Floating+Cogs&amp;pulls=1&amp;stargazers=1&amp;theme=Dark" alt="project-image"></p>

<p id="description">SQL code for storing Hill Climb Racing Adventure World Records into a database. It features a file called main.sql containing all four tables for players, adventure maps, vehicles, and Hill Climb Racing 2 Adventure World Records. The inserted data is constantly updated to ensure all World Records are up to date. Now, you can also view data from a simple website and even edit it!</p>

<h2>Project Screenshots:</h2>

<img src="https://i.ibb.co/9mVXNMdC/Kuvakaappaus-2025-05-07-19-33-08.png" alt="project-screenshot" width="1200" height="800/">

<h1>How to install?</h1>
<p>
</p>
<h2>Linux:</h2>
1. Install project from github:
```
git clone https://github.com/Nipatsuplayer/hcr2-database-with-website.git
```
2. Install requirements:
```
sudo apt update
sudo apt install php sqlite3
```
3. Run project:
```
cd /home/your-username/hcr2-adventure-database/ (replace `/home/your-username/hcr2-adventure-database/` with the actual path to the project folder on your system)
php -S localhost:8000
```
After these steps open your browser and write `localhost:8000` and hit enter. That's it!

<h2>Windows:</h2>
<h3>Install git for windows</h3>
Git for Windows is not installed by default. Follow these steps to install it:
1. Go to [official](https://git-scm.com/download/win) website and download latest version
2. After downloading run installer and if windows asks for permission click **yes**
3. Installation steps
`Choose editor` → Leave as `Use vim` or choose some popular app like notepad
`Adjust your PATH environment` → Make sure `Git from the command line and also from 3rd-party software` is selected
Leave everything else at default and click next until you reach install
4. Click "Finish" once the installer completes
5. Open command prompt and type:
```
git --version
```
If it returns git version you're all good and can continue to next steps!

<h3>Install PHP for windows</h3>
1. Go to https://windows.php.net/download and download **Thread safe** ZIP version (recommended for local development as it ensures thread safety when running PHP as a built-in server).
2. Extract it to a folder (for example C:\php). Ensure the folder path does not contain spaces to avoid potential issues with command-line tools.
3. Press windows key and type `Environment variables` and click `Edit the system environment variables`
4. In new window click `Environment variables...` button
5. In system variables section find and click variable named `Path`, then click `edit`
6. Click `new` and type:
```
C:\php
```
(or folder where you extracted PHP)
7. Click `OK` on all windows to save and exit
8. Open command prompt and type:
```
php -v
```
If you see php version info, PHP is succesfully installed and you can continue!

<h3>Run website</h3>
1. Type this command in command prompt:
```
php -m
```
Check that `sqlite3` is listed under PHP modules. If not, enable it by editing the `php.ini` file and removing the semicolon (`;`) at the beginning of the line `;extension=sqlite3` to uncomment it.
2. After that go to project folder:
```
cd path\to\hcr2-database-with-website
```
3. Run website with command:
```
php -S localhost:8000
```
After these steps open your browser and write `localhost:8000` and hit enter. That's it!

<h2>Updating database</h2>
Titanium regularly updates the World Record database. But how do you get the latest data into this project?

First, it's important to understand that `.sql` files and `.sqlite` files are not the same. You cannot simply rename `.sql` file to `.sqlite` — that won't work. `.sql` files contain plain text SQL commands, while `.sqlite` files are actual database files.

To update the database, you need to import the data using code. Luckily, I’ve already written the necessary scripts for you — all you need to do is run a few simple commands in your terminal. See below for a step-by-step guide.

<h3>Install Python</h3>
<h4>Linux:</h4>
```
sudo apt update
sudo apt install python3
sudo apt install sqlite3  # SQLite should already be installed, but make sure
```
<h4>Windows:</h4>
Go to [Official python website](https://www.python.org/downloads/) and install latest version.
Make sure that Python and Pip are added to the system PATH during installation. To verify, open a terminal or command prompt and type `python --version` and `pip --version`. If these commands are not recognized, you may need to manually add Python and Pip to the PATH. For guidance, chech `installing PHP for windows` and there steps 3-7

<h3>How to use commands</h3>
<h4>Linux:</h4>
- Open terminal and go to project folder
- run following command to export database (.sqlite) to SQL file (.sql):
```
python3 movedata.py export --db main.sqlite --out main.sql
```
- run following command to import SQL file (.sql) into SQLite database (.sqlite):
```
python3 movedata.py import --db main.sqlite --in main.sql
```
You can also view info about all commands with this command:
```
python3 movedata.py --help
```

<h4>Windows:</h4>
- Open command prompt and go to project folder
- run following command to export database (.sqlite) to SQL file (.sql):
```
python movedata.py export --db main.sqlite --out main.sql
```
- run following command to import SQL file (.sql) into SQlite database (.sqlite):
```
python movedata.py import --db main.sqlite --in main.sql
```
You can also view info about all commands with this command:
```
python movedata.py --help
```

<h2>💻 Built with</h2>

Technologies used in the project:

*   HTML
*   PHP
*   SQL