# HCR2 ADVENTURE DATABASE + WEBSITE

<p align="center">
  <img src="https://socialify.git.ci/Nipatsuplayer/hcr2-database-with-website/image?font=Bitter&forks=1&issues=1&language=1&name=1&owner=1&pattern=Floating+Cogs&pulls=1&stargazers=1&theme=Dark" alt="project-image">
</p>

SQL code for storing Hill Climb Racing Adventure World Records into a database.  
Includes `main.sql` with all 4 tables: players, maps, vehicles, and WRs.  
The inserted data is updated regularly. Now features a simple web UI to view and edit data.

---

## 📸 Project Screenshots

<img src="https://i.ibb.co/9mVXNMdC/Kuvakaappaus-2025-05-07-19-33-08.png" alt="project-screenshot" width="100%">

---

# 🚀 How to Install

## 🐧 Linux

1. Install the project:

   ```bash
   git clone https://github.com/Nipatsuplayer/hcr2-database-with-website.git
   ```

2. Install requirements:

   ```bash
   sudo apt update
   sudo apt install php sqlite3
   ```

3. Run the project:

   ```bash
   cd /home/your-username/hcr2-database-with-website
   php -S localhost:8000
   ```

4. Open your browser and go to `localhost:8000`

---

## 🪟 Windows

### Install Git

1. Download from [git-scm.com](https://git-scm.com/download/win)  
2. Run the installer → allow access  
3. Choose editor (e.g., Notepad or use vim)  
4. On **PATH settings**, choose: `Git from the command line and 3rd-party software`  
5. Finish setup and verify with:

   ```bash
   git --version
   ```

### Install PHP

1. Download **Thread Safe ZIP** from [windows.php.net](https://windows.php.net/download)  
2. Extract to `C:\php`  
3. Add `C:\php` to PATH:

   - Press ⊞ Win → "Environment Variables" → Edit `Path`
   - Add new: `C:\php`
   - Save & restart cmd

4. Check version:

   ```bash
   php -v
   ```

### Run Website

1. Check SQLite module:

   ```bash
   php -m
   ```

   - If `sqlite3` is missing, open `php.ini` and uncomment:

     ```
     extension=sqlite3
     ```

2. Go to project folder:

   ```bash
   cd path\to\hcr2-database-with-website
   ```

3. Run the server:

   ```bash
   php -S localhost:8000
   ```

4. Open your browser and go to `localhost:8000`

---

## 🔄 Updating the Database

`.sql` and `.sqlite` are **not the same thing**.  
You can't rename `.sql` → `.sqlite` — instead, use the included Python script.

### 🐍 Install Python

#### Linux:

```bash
sudo apt update
sudo apt install python3 sqlite3
```

#### Windows:

Download from [python.org](https://www.python.org/downloads/)  
During install, check "Add Python to PATH"  
Then check:

```bash
python --version
pip --version
```

---

### 🔧 Use Commands

#### Linux:

```bash
# Export:
python3 movedata.py export --db main.sqlite --out main.sql

# Import:
python3 movedata.py import --db main.sqlite --in main.sql

# Help:
python3 movedata.py --help
```

#### Windows:

```bash
# Export:
python movedata.py export --db main.sqlite --out main.sql

# Import:
python movedata.py import --db main.sqlite --in main.sql

# Help:
python movedata.py --help
```

---

## 💻 Built With

- HTML  
- PHP  
- SQL  