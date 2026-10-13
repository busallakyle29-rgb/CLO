$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
$pptPath = Join-Path $root 'docs\CLO_Complete_System_Workflow.pptx'
$screenshotPath = Join-Path $root 'docs\workflow_images\system_import.png'
$logoPath = Join-Path $root 'assets\logo\CLO_LOGO.png'
$app = $null
$presentation = $null

function Add-ImportSlideText($slide, [string]$text, [double]$x, [double]$y, [double]$w, [double]$h, [double]$size, [int]$color, [bool]$bold = $false) {
    $shape = $slide.Shapes.AddTextbox(1, $x, $y, $w, $h)
    $shape.Fill.Visible = 0
    $shape.Line.Visible = 0
    $shape.TextFrame.MarginLeft = 0
    $shape.TextFrame.MarginRight = 0
    $shape.TextFrame.MarginTop = 0
    $shape.TextFrame.MarginBottom = 0
    $shape.TextFrame.WordWrap = -1
    $range = $shape.TextFrame.TextRange
    $range.Text = $text
    $range.Font.Name = 'Aptos'
    $range.Font.Size = $size
    $range.Font.Bold = $bold
    $range.Font.Color.RGB = $color
}

if (-not (Test-Path -LiteralPath $pptPath)) { throw "Presentation not found: $pptPath" }
if (-not (Test-Path -LiteralPath $screenshotPath)) { throw "Import screenshot not found: $screenshotPath" }

try {
    $app = New-Object -ComObject PowerPoint.Application
    $app.Visible = -1
    $presentation = $app.Presentations.Open($pptPath, $false, $false, $false)

    $slide = $presentation.Slides.Add($presentation.Slides.Count + 1, 12)
    $slide.FollowMasterBackground = 0
    $slide.Background.Fill.ForeColor.RGB = 16777215

    $header = $slide.Shapes.AddShape(1, 0, 0, 960, 86)
    $header.Fill.ForeColor.RGB = 4723472
    $header.Line.Visible = 0
    Add-ImportSlideText $slide 'Actual System: Import Documents' 36 15 810 34 25 16777215 $true
    Add-ImportSlideText $slide 'INCOMING staff can upload and validate spreadsheet rows before importing documents.' 38 53 810 20 11 16445159
    $slide.Shapes.AddPicture($logoPath, 0, -1, 897, 13, 48, 48) | Out-Null

    $frame = $slide.Shapes.AddShape(5, 32, 112, 594, 337)
    $frame.Fill.ForeColor.RGB = 16777215
    $frame.Line.ForeColor.RGB = 14474460
    $frame.Line.Weight = 1
    $slide.Shapes.AddPicture($screenshotPath, 0, -1, 38, 118, 582, 325) | Out-Null

    Add-ImportSlideText $slide 'IMPORT STEPS' 656 125 256 26 17 4723472 $true
    $steps = @(
        @('1. Select workbook', 'Choose a valid .xlsx file using the sample template.'),
        @('2. Preview and validate', 'Review rows and fix missing or invalid values.'),
        @('3. Confirm import', 'Import only after the preview is correct.'),
        @('4. Verify records', 'Check generated tracking numbers and assignments.')
    )
    $y = 171
    foreach ($step in $steps) {
        $card = $slide.Shapes.AddShape(5, 650, $y, 276, 69)
        $card.Fill.ForeColor.RGB = 15986665
        $card.Line.ForeColor.RGB = 14474460
        Add-ImportSlideText $slide $step[0] 664 ($y + 9) 250 19 13 4723472 $true
        Add-ImportSlideText $slide $step[1] 664 ($y + 31) 250 32 10 4343879
        $y += 77
    }
    Add-ImportSlideText $slide 'Up to 500 rows and 10 MB per workbook. Required columns: Title, Document Type, Origin Department, Date Received.' 654 488 272 33 10 8094555

    $rule = $slide.Shapes.AddShape(1, 36, 510, 888, 1)
    $rule.Fill.ForeColor.RGB = 14474460
    $rule.Line.Visible = 0
    Add-ImportSlideText $slide 'CLO DOCUMENT TRACKING SYSTEM  |  ACTUAL SYSTEM SCREENSHOT' 36 518 760 14 8 8094555
    Add-ImportSlideText $slide ('{0:D2}' -f $presentation.Slides.Count) 875 516 48 17 9 8094555 $true

    $presentation.Save()
    Write-Output "Added Import Documents screenshot slide. Total slides: $($presentation.Slides.Count)."
}
finally {
    if ($presentation -ne $null) { $presentation.Close() }
    if ($app -ne $null) {
        $app.Quit()
        if ($presentation -ne $null) { [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($presentation) }
        [void][System.Runtime.InteropServices.Marshal]::ReleaseComObject($app)
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
