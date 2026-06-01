<?php require_once 'session_check_resident.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File a Complaint - Barangay San Roque</title>
    <link rel="stylesheet" href="portal-style.css">
    <link rel="stylesheet" href="form-style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .upload-box{background:#fdfdfd;border:2px dashed #c6d9ce;padding:28px 20px;border-radius:10px;text-align:center;transition:border-color 0.2s,background 0.2s;cursor:pointer;}
        .upload-box:hover,.upload-box.dragover{border-color:#2D6A4F;background:#f0faf5;}
        .upload-box label{font-size:.9rem;font-weight:600;color:#2D6A4F;cursor:pointer;display:block;margin-bottom:6px;}
        .upload-box input[type="file"]{display:none;}
        .upload-icon{font-size:2rem;margin-bottom:8px;display:block;}
        .file-hint{font-size:.75rem;color:#999;margin-top:6px;}
        .size-bar-wrap{margin-top:14px;display:none;}.size-bar-wrap.visible{display:block;}
        .size-bar-track{background:#e8f0eb;border-radius:99px;height:7px;overflow:hidden;margin-bottom:5px;}
        .size-bar-fill{height:100%;background:#2D6A4F;border-radius:99px;transition:width .3s ease,background .3s;width:0%;}
        .size-bar-fill.warning{background:#e67e22;}.size-bar-fill.over{background:#e53e3e;}
        .size-label{font-size:11px;color:#64748b;text-align:right;}
        .file-pill-list{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;}
        .file-pill{display:flex;align-items:center;gap:6px;background:#f0faf5;border:1px solid #c6d9ce;border-radius:99px;padding:5px 12px 5px 10px;font-size:12px;color:#2D6A4F;font-weight:500;max-width:220px;}
        .file-pill span{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px;}
        .file-pill .remove-file{cursor:pointer;color:#94a3b8;font-size:15px;line-height:1;flex-shrink:0;transition:color .15s;}
        .file-pill .remove-file:hover{color:#e53e3e;}
        .upload-error{color:#e53e3e;font-size:12px;margin-top:8px;display:none;}.upload-error.show{display:block;}
    </style>
</head>
<body>
<?php include 'resident-sidebar.php'; ?>
<main class="portal-content">
    <div class="top-nav"><a href="resident-portal.php" class="back-link">← Back to Dashboard</a></div>
    <header class="form-header">
        <h2>OFFICE OF THE SANGGUNIANG BARANGAY</h2>
        <p>Barangay San Roque, Marikina City</p>
        <p>Republic of the Philippines - National Capital Region</p>
        <small>KP Form No. 9 - APPENDIX "H"</small>
    </header>
    <section class="form-container">
        <h1 class="form-title">SUMBONG</h1>
        <p class="form-intro">Ako/Kami sa pamamagitan nito ay naghahain ng sumbong laban sa mga ipinagsusumbong na binanggit sa itaas dahil sa paglabag sa aking/aming karapatan sa sumusunod na paraan:</p>
        <form action="submit_complaint.php" method="POST" enctype="multipart/form-data" id="complaintForm">
            <div class="form-row">
                <div class="form-group"><label>Usapin Blg. / Subject *</label><input type="text" name="subject" placeholder="e.g., Noise Complaint, Property Dispute" required></div>
                <div class="form-group"><label>Date</label><input type="date" name="complaint_date" value="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="section-header"><img src="people.png" class="section-icon"><h3>Mga May Sumbong (Complainant Information)</h3></div>
            <div class="form-group"><label>Full Name(s) *</label><input type="text" name="complainant_name" value="<?php echo htmlspecialchars($session_resident_name); ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label>Address *</label><input type="text" name="complainant_address" placeholder="Complete address" required></div>
                <div class="form-group"><label>Contact Number *</label><input type="text" name="complainant_contact" placeholder="+63 XXX XXX XXXX" required></div>
            </div>
            <div class="section-header"><img src="people.png" class="section-icon"><h3>Mga Ipinagsusumbong (Respondent Information)</h3></div>
            <div class="form-group"><label>Full Name(s) *</label><input type="text" name="respondent_name" placeholder="Enter respondent's full name" required></div>
            <div class="form-group"><label>Address *</label><input type="text" name="respondent_address" placeholder="Complete address" required></div>
            <div class="section-header"><img src="file.png" class="section-icon"><h3>Complaint Details</h3></div>
            <div class="form-group"><label>Narrative / Statement *</label><textarea name="narrative" placeholder="DAHIL DITO Ako/Kami ay namamantik na ipagkaloob..." required></textarea></div>
            <div class="form-group"><label>Desired Resolution</label><textarea name="resolution" placeholder="What outcome are you seeking?"></textarea></div>
            <div class="section-header"><img src="upload.png" class="section-icon"><h3>Valid ID and other support documents</h3></div>
            <div class="upload-box" id="uploadBox">
                <span class="upload-icon">📎</span>
                <label for="file-upload">Click to add files or drag & drop here</label>
                <input type="file" name="evidence[]" id="file-upload" multiple>
                <p class="file-hint">PDF, JPG, PNG, DOC and more — up to 25 MB total</p>
            </div>
            <div class="size-bar-wrap" id="sizeBarWrap"><div class="size-bar-track"><div class="size-bar-fill" id="sizeBarFill"></div></div><div class="size-label" id="sizeLabel">0 MB / 25 MB</div></div>
            <p class="upload-error" id="uploadError">⚠ Total file size exceeds 25 MB. Please remove some files.</p>
            <div class="file-pill-list" id="filePillList"></div>
            <div class="form-footer">
                <p>Tinanggap at itinala ngayong ika - <?php echo date('d'); ?> araw ng <?php echo date('F, Y'); ?>.</p>
                <p>(Mga) May Sumbong / Complainant(s)</p>
                <strong>TADEO ALLAN M. ARAMIL</strong><span>Punong Barangay</span>
            </div>
            <div class="form-actions">
                <button type="submit" class="submit-btn" id="submitBtn">Submit Complaint</button>
                <button type="button" class="cancel-btn" onclick="window.history.back()">Cancel</button>
            </div>
        </form>
    </section>
</main>
<div id="successModal" class="modal-overlay" style="display:none !important;">
    <div class="modal-content">
        <div class="success-icon"><img src="secure.png" alt="Success"></div>
        <h2>Submission Successful!</h2>
        <p>Your complaint has been successfully passed and is now waiting for admin case review.</p>
        <div class="modal-choices">
            <button class="view-btn" onclick="goToViewComplaint()">View Submitted Complaint</button>
            <button class="return-btn" onclick="goToDashboard()">Back to Dashboard</button>
        </div>
    </div>
</div>
<script>
    (function(){const p=new URLSearchParams(window.location.search);if(p.has('success'))document.getElementById('successModal').style.setProperty('display','flex','important');})();
    function goToDashboard(){window.location.href='resident-portal.php';}
    function goToViewComplaint(){window.location.href='view-complaint.php?id='+new URLSearchParams(window.location.search).get('id');}
    const MAX=25*1024*1024,inp=document.getElementById('file-upload'),plist=document.getElementById('filePillList'),sw=document.getElementById('sizeBarWrap'),sb=document.getElementById('sizeBarFill'),sl=document.getElementById('sizeLabel'),ue=document.getElementById('uploadError'),sbtn=document.getElementById('submitBtn'),ub=document.getElementById('uploadBox');
    let fm=new Map(),ni=0;
    function fs(b){if(b<1024)return b+' B';if(b<1048576)return(b/1024).toFixed(1)+' KB';return(b/1048576).toFixed(2)+' MB';}
    function ts(){let t=0;fm.forEach(f=>t+=f.size);return t;}
    function ru(){const t=ts(),p=Math.min((t/MAX)*100,100),o=t>MAX;sw.classList.toggle('visible',fm.size>0);sb.style.width=p+'%';sb.className='size-bar-fill'+(o?' over':p>80?' warning':'');sl.textContent=fs(t)+' / 25 MB';ue.classList.toggle('show',o);sbtn.disabled=o;sbtn.style.opacity=o?'0.5':'1';}
    function ri(){const dt=new DataTransfer();fm.forEach(f=>dt.items.add(f));inp.files=dt.files;}
    function af(nf){Array.from(nf).forEach(file=>{let d=false;fm.forEach(f=>{if(f.name===file.name&&f.size===file.size)d=true;});if(d)return;const id=ni++;fm.set(id,file);const pill=document.createElement('div');pill.className='file-pill';pill.innerHTML=`<span title="${file.name}">📄 ${file.name}</span><span class="remove-file">×</span>`;pill.querySelector('.remove-file').addEventListener('click',()=>{fm.delete(id);pill.remove();ri();ru();});plist.appendChild(pill);});ri();ru();}
    ub.addEventListener('click',e=>{if(e.target!==inp)inp.click();});
    inp.addEventListener('change',()=>{af(inp.files);inp.value='';});
    ub.addEventListener('dragover',e=>{e.preventDefault();ub.classList.add('dragover');});
    ub.addEventListener('dragleave',()=>ub.classList.remove('dragover'));
    ub.addEventListener('drop',e=>{e.preventDefault();ub.classList.remove('dragover');af(e.dataTransfer.files);});
    document.getElementById('complaintForm').addEventListener('submit',e=>{if(ts()>MAX){e.preventDefault();ue.classList.add('show');}});
</script>
</body>
</html>
