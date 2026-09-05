param(
    [ValidateSet("backup","compare","local-to-cloud","cloud-to-local")]
    [string]$Action = "compare",

    [ValidateSet("data","full")]
    [string]$Mode = "data"
)

$ErrorActionPreference = "Stop"

# ============================================================
# MYSQL LOCAL
# ============================================================

$MysqlExe = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe"
$MysqldumpExe = "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe"

$LocalHost = "127.0.0.1"
$LocalPort = 3306
$LocalDb = "techstore"
$LocalUser = "root"

# ============================================================
# LARAVEL CLOUD
# ============================================================

$CloudHost = "db-a2aac114-0b0e-431a-b0c9-fba45f64f79a.ap-southeast-1.public.db.laravel.cloud"
$CloudPort = 3306
$CloudDb = "production"
$CloudUser = "vlr7tdiwrpjfkgfi"

# ============================================================
# BUSINESS TABLES
# ============================================================

$BusinessTables = @(
    "product_categories",
    "admin_products",
    "coupons",
    "shipping_fees"
)

# Runtime tables - không đồng bộ ở DATA mode
$RuntimeTables = @(
    "sessions",
    "cache",
    "cache_locks",
    "jobs",
    "failed_jobs",
    "job_batches",
    "password_reset_tokens"
)

# ============================================================
# BACKUP DIRECTORY
# ============================================================

$BackupDir = Join-Path $PSScriptRoot "database-backups"

New-Item `
    -ItemType Directory `
    -Force `
    -Path $BackupDir |
    Out-Null

# ============================================================
# CHECK TOOLS
# ============================================================

function Assert-Tools {

    if (-not (Test-Path $MysqlExe)) {
        throw "Không tìm thấy mysql.exe: $MysqlExe"
    }

    if (-not (Test-Path $MysqldumpExe)) {
        throw "Không tìm thấy mysqldump.exe: $MysqldumpExe"
    }
}

# ============================================================
# PASSWORD INPUT
# ============================================================

function Read-SecretText([string]$Prompt) {

    $Secure = Read-Host `
        -Prompt $Prompt `
        -AsSecureString

    $Ptr = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($Secure)

    try {
        return [Runtime.InteropServices.Marshal]::PtrToStringBSTR($Ptr)
    }
    finally {
        [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($Ptr)
    }
}

# ============================================================
# MYSQL QUERY
# ============================================================

function Invoke-Mysql {

    param(
        [string]$DbHost,
        [int]$Port,
        [string]$User,
        [string]$Database,
        [string]$Sql,
        [string]$Password
    )

    $env:MYSQL_PWD = $Password

    try {

        $output = & $MysqlExe `
            --protocol=TCP `
            --host=$DbHost `
            --port=$Port `
            --user=$User `
            --default-character-set=utf8mb4 `
            --batch `
            --raw `
            --skip-column-names `
            $Database `
            -e $Sql

        if ($LASTEXITCODE -ne 0) {
            throw "mysql.exe thất bại. Exit code: $LASTEXITCODE"
        }

        return $output
    }
    finally {

        Remove-Item `
            Env:MYSQL_PWD `
            -ErrorAction SilentlyContinue
    }
}

# ============================================================
# GET TABLE LIST
# ============================================================

function Get-TableList {

    param(
        [string]$DbHost,
        [int]$Port,
        [string]$User,
        [string]$Database,
        [string]$Password
    )

    $sql = @"
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '$Database'
  AND TABLE_TYPE = 'BASE TABLE'
ORDER BY TABLE_NAME;
"@

    $output = Invoke-Mysql `
        -DbHost $DbHost `
        -Port $Port `
        -User $User `
        -Database $Database `
        -Password $Password `
        -Sql $sql

    $tables = @()

    foreach ($line in $output) {

        $table = $line.ToString().Trim()

        if ($table) {
            $tables += $table
        }
    }

    return $tables
}

# ============================================================
# GET TABLE COUNTS
# ============================================================

function Get-TableCounts {

    param(
        [string]$DbHost,
        [int]$Port,
        [string]$User,
        [string]$Database,
        [string]$Password,
        [string[]]$Tables
    )

    $result = @{}

    foreach ($table in $Tables) {

        $sql = "SELECT COUNT(*) FROM ``$table``;"

        $output = Invoke-Mysql `
            -DbHost $DbHost `
            -Port $Port `
            -User $User `
            -Database $Database `
            -Password $Password `
            -Sql $sql

        $count = ($output | Select-Object -Last 1).ToString().Trim()

        $result[$table] = [int64]$count
    }

    return $result
}

# ============================================================
# SELECT TABLES
# ============================================================

function Get-SelectedTables {

    param(
        [string]$Mode,
        [string[]]$LocalTables,
        [string[]]$CloudTables
    )

    if ($Mode -eq "data") {

        return $BusinessTables |
            Where-Object {
                ($LocalTables -contains $_) -and
                ($CloudTables -contains $_)
            }
    }

    # FULL:
    # Local là nguồn chuẩn.
    # Lấy toàn bộ bảng có trong Local,
    # ngoại trừ runtime tables.

    return $LocalTables |
        Where-Object {
            $RuntimeTables -notcontains $_
        }
}

# ============================================================
# SAFE MYSQL DUMP
# ============================================================

function Export-Database {

    param(
        [string]$DbHost,
        [int]$Port,
        [string]$User,
        [string]$Database,
        [string]$Password,
        [string[]]$Tables,
        [string]$OutputFile
    )

    Write-Host ""
    Write-Host "EXPORT DATABASE" -ForegroundColor Cyan
    Write-Host "Database : $Database"
    Write-Host "Output   : $OutputFile"

    $env:MYSQL_PWD = $Password

    try {

        $args = @(
            "--protocol=TCP"
            "--host=$DbHost"
            "--port=$Port"
            "--user=$User"

            "--single-transaction"
            "--skip-lock-tables"

            "--routines"
            "--triggers"
            "--events"

            "--hex-blob"

            "--default-character-set=utf8mb4"

            "--result-file=$OutputFile"

            $Database
        )

        if ($Tables -and $Tables.Count -gt 0) {

            $args += $Tables
        }

        & $MysqldumpExe @args

        if ($LASTEXITCODE -ne 0) {

            throw "mysqldump thất bại. Exit code: $LASTEXITCODE"
        }
    }
    finally {

        Remove-Item `
            Env:MYSQL_PWD `
            -ErrorAction SilentlyContinue
    }

    if (-not (Test-Path $OutputFile)) {

        throw "Không tạo được file dump: $OutputFile"
    }

    $size = (Get-Item $OutputFile).Length

    if ($size -le 0) {

        throw "File dump rỗng: $OutputFile"
    }

    Write-Host "Dump OK - $size bytes" -ForegroundColor Green

    return $OutputFile
}

# ============================================================
# SAFE MYSQL IMPORT
# ============================================================

function Import-SqlFile {

    param(
        [string]$DbHost,
        [int]$Port,
        [string]$User,
        [string]$Database,
        [string]$Password,
        [string]$SqlFile
    )

    if (-not (Test-Path $SqlFile)) {

        throw "Không tìm thấy SQL file: $SqlFile"
    }

    Write-Host ""
    Write-Host "IMPORT DATABASE" -ForegroundColor Cyan
    Write-Host "Database : $Database"
    Write-Host "SQL file : $SqlFile"

    $env:MYSQL_PWD = $Password

    try {

        # Dùng CMD để chuyển file trực tiếp vào mysql.exe.
        # Không dùng Get-Content / Set-Content.

        $mysqlCommand = @(
            "`"$MysqlExe`""
            "--protocol=TCP"
            "--host=$DbHost"
            "--port=$Port"
            "--user=$User"
            "--default-character-set=utf8mb4"
            "--binary-mode=1"
            $Database
            "<"
            "`"$SqlFile`""
        ) -join " "

        & cmd.exe /d /s /c $mysqlCommand

        if ($LASTEXITCODE -ne 0) {

            throw "Import MySQL thất bại. Exit code: $LASTEXITCODE"
        }
    }
    finally {

        Remove-Item `
            Env:MYSQL_PWD `
            -ErrorAction SilentlyContinue
    }

    Write-Host "Import OK." -ForegroundColor Green
}

# ============================================================
# BACKUP
# ============================================================

function Backup-Database {

    param(
        [string]$DbHost,
        [int]$Port,
        [string]$User,
        [string]$Database,
        [string]$Password,
        [string]$Label
    )

    $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

    $file = Join-Path `
        $BackupDir `
        "${Label}_${Database}_${timestamp}.sql"

    Write-Host ""
    Write-Host "BACKUP -> $file" -ForegroundColor Yellow

    Export-Database `
        -DbHost $DbHost `
        -Port $Port `
        -User $User `
        -Database $Database `
        -Password $Password `
        -Tables @() `
        -OutputFile $file

    Write-Host "Backup hoàn tất." -ForegroundColor Green

    return $file
}

# ============================================================
# CHECK TOOLS
# ============================================================

Assert-Tools

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "       TECHSTORE DATABASE SYNC" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Action : $Action"
Write-Host "Mode   : $Mode"
Write-Host ""

# ============================================================
# ACTION
# ============================================================

switch ($Action) {

    # --------------------------------------------------------
    # BACKUP
    # --------------------------------------------------------

    "backup" {

        $target = Read-Host "Backup nào? (local/cloud)"

        if ($target -eq "local") {

            $pwd = Read-SecretText "Mật khẩu MySQL Local"

            Backup-Database `
                -DbHost $LocalHost `
                -Port $LocalPort `
                -User $LocalUser `
                -Database $LocalDb `
                -Password $pwd `
                -Label "local" |
                Out-Host
        }
        elseif ($target -eq "cloud") {

            $pwd = Read-SecretText "Mật khẩu MySQL Cloud"

            Backup-Database `
                -DbHost $CloudHost `
                -Port $CloudPort `
                -User $CloudUser `
                -Database $CloudDb `
                -Password $pwd `
                -Label "cloud" |
                Out-Host
        }
        else {

            throw "Chỉ được chọn local hoặc cloud."
        }

        break
    }

    # --------------------------------------------------------
    # COMPARE
    # --------------------------------------------------------

    "compare" {

        $localPwd = Read-SecretText "Mật khẩu MySQL Local"
        $cloudPwd = Read-SecretText "Mật khẩu MySQL Cloud"

        $localTables = Get-TableList `
            -DbHost $LocalHost `
            -Port $LocalPort `
            -User $LocalUser `
            -Database $LocalDb `
            -Password $localPwd

        $cloudTables = Get-TableList `
            -DbHost $CloudHost `
            -Port $CloudPort `
            -User $CloudUser `
            -Database $CloudDb `
            -Password $cloudPwd

        $tables = Get-SelectedTables `
            -Mode $Mode `
            -LocalTables $localTables `
            -CloudTables $cloudTables

        Write-Host ""
        Write-Host "BẢNG SO SÁNH" -ForegroundColor Yellow
        Write-Host ("{0,-32} {1,12} {2,12}" -f "Table","LOCAL","CLOUD")
        Write-Host ("-" * 68)

        $localCounts = Get-TableCounts `
            -DbHost $LocalHost `
            -Port $LocalPort `
            -User $LocalUser `
            -Database $LocalDb `
            -Password $localPwd `
            -Tables $tables

        $cloudCounts = Get-TableCounts `
            -DbHost $CloudHost `
            -Port $CloudPort `
            -User $CloudUser `
            -Database $CloudDb `
            -Password $cloudPwd `
            -Tables $tables

        foreach ($table in $tables) {

            $lc = $localCounts[$table]
            $cc = $cloudCounts[$table]

            $mark = if ($lc -eq $cc) {
                "OK"
            }
            else {
                "DIFF"
            }

            Write-Host (
                "{0,-32} {1,12} {2,12}  {3}" `
                -f $table,$lc,$cc,$mark
            )
        }

        Write-Host ""
        Write-Host "LOCAL tables : $($localTables.Count)"
        Write-Host "CLOUD tables : $($cloudTables.Count)"
        Write-Host "Compare      : $($tables.Count) tables"

        break
    }

    # --------------------------------------------------------
    # LOCAL -> CLOUD
    # --------------------------------------------------------

    "local-to-cloud" {

        $localPwd = Read-SecretText "Mật khẩu MySQL Local"
        $cloudPwd = Read-SecretText "Mật khẩu MySQL Cloud"

        $localTables = Get-TableList `
            -DbHost $LocalHost `
            -Port $LocalPort `
            -User $LocalUser `
            -Database $LocalDb `
            -Password $localPwd

        $cloudTables = Get-TableList `
            -DbHost $CloudHost `
            -Port $CloudPort `
            -User $CloudUser `
            -Database $CloudDb `
            -Password $cloudPwd

        $tables = Get-SelectedTables `
            -Mode $Mode `
            -LocalTables $localTables `
            -CloudTables $cloudTables

        if ($tables.Count -eq 0) {

            throw "Không có bảng nào để đồng bộ."
        }

        Write-Host ""
        Write-Host "Các bảng sẽ đồng bộ LOCAL -> CLOUD:" `
            -ForegroundColor Yellow

        $tables |
            ForEach-Object {
                Write-Host " - $_"
            }

        Write-Host ""
        Write-Host "LOCAL là nguồn dữ liệu chuẩn." `
            -ForegroundColor Cyan

        $confirm = Read-Host "Gõ SYNC để tiếp tục"

        if ($confirm -ne "SYNC") {

            Write-Host "Đã hủy." -ForegroundColor Yellow
            break
        }

        # ----------------------------------------------------
        # BACKUP CLOUD
        # ----------------------------------------------------

        Backup-Database `
            -DbHost $CloudHost `
            -Port $CloudPort `
            -User $CloudUser `
            -Database $CloudDb `
            -Password $cloudPwd `
            -Label "cloud_before_local_to_cloud" |
            Out-Host

        # ----------------------------------------------------
        # EXPORT LOCAL
        # ----------------------------------------------------

        $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

        $dumpFile = Join-Path `
            $BackupDir `
            "local_to_cloud_${timestamp}.sql"

        Export-Database `
            -DbHost $LocalHost `
            -Port $LocalPort `
            -User $LocalUser `
            -Database $LocalDb `
            -Password $localPwd `
            -Tables $tables `
            -OutputFile $dumpFile

        # ----------------------------------------------------
        # IMPORT CLOUD
        # ----------------------------------------------------

        Import-SqlFile `
            -DbHost $CloudHost `
            -Port $CloudPort `
            -User $CloudUser `
            -Database $CloudDb `
            -Password $cloudPwd `
            -SqlFile $dumpFile

        Write-Host ""
        Write-Host "============================================" `
            -ForegroundColor Green

        Write-Host "LOCAL -> CLOUD HOÀN TẤT" `
            -ForegroundColor Green

        Write-Host "============================================" `
            -ForegroundColor Green

        break
    }

    # --------------------------------------------------------
    # CLOUD -> LOCAL
    # --------------------------------------------------------

    "cloud-to-local" {

        $localPwd = Read-SecretText "Mật khẩu MySQL Local"
        $cloudPwd = Read-SecretText "Mật khẩu MySQL Cloud"

        $localTables = Get-TableList `
            -DbHost $LocalHost `
            -Port $LocalPort `
            -User $LocalUser `
            -Database $LocalDb `
            -Password $localPwd

        $cloudTables = Get-TableList `
            -DbHost $CloudHost `
            -Port $CloudPort `
            -User $CloudUser `
            -Database $CloudDb `
            -Password $cloudPwd

        $tables = Get-SelectedTables `
            -Mode $Mode `
            -LocalTables $localTables `
            -CloudTables $cloudTables

        if ($tables.Count -eq 0) {

            throw "Không có bảng nào để đồng bộ."
        }

        Write-Host ""
        Write-Host "Các bảng sẽ đồng bộ CLOUD -> LOCAL:" `
            -ForegroundColor Yellow

        $tables |
            ForEach-Object {
                Write-Host " - $_"
            }

        $confirm = Read-Host "Gõ SYNC để tiếp tục"

        if ($confirm -ne "SYNC") {

            Write-Host "Đã hủy." -ForegroundColor Yellow
            break
        }

        # ----------------------------------------------------
        # BACKUP LOCAL
        # ----------------------------------------------------

        Backup-Database `
            -DbHost $LocalHost `
            -Port $LocalPort `
            -User $LocalUser `
            -Database $LocalDb `
            -Password $localPwd `
            -Label "local_before_cloud_to_local" |
            Out-Host

        # ----------------------------------------------------
        # EXPORT CLOUD
        # ----------------------------------------------------

        $timestamp = Get-Date -Format "yyyyMMdd_HHmmss"

        $dumpFile = Join-Path `
            $BackupDir `
            "cloud_to_local_${timestamp}.sql"

        Export-Database `
            -DbHost $CloudHost `
            -Port $CloudPort `
            -User $CloudUser `
            -Database $CloudDb `
            -Password $cloudPwd `
            -Tables $tables `
            -OutputFile $dumpFile

        # ----------------------------------------------------
        # IMPORT LOCAL
        # ----------------------------------------------------

        Import-SqlFile `
            -DbHost $LocalHost `
            -Port $LocalPort `
            -User $LocalUser `
            -Database $LocalDb `
            -Password $localPwd `
            -SqlFile $dumpFile

        Write-Host ""
        Write-Host "============================================" `
            -ForegroundColor Green

        Write-Host "CLOUD -> LOCAL HOÀN TẤT" `
            -ForegroundColor Green

        Write-Host "============================================" `
            -ForegroundColor Green

        break
    }
}