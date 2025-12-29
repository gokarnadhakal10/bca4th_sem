<?php
session_start();
require "config.php";

if(!isset($_SESSION['voter_id'])){
    header("Location: login.php"); 
    exit;
}

$voter_id = $_SESSION['voter_id'];
$voter = $conn->query("SELECT * FROM users WHERE id=$voter_id")->fetch_assoc();

// Get latest voting session
$vote_session = $conn->query("SELECT * FROM voting_session ORDER BY id DESC LIMIT 1")->fetch_assoc();
$now = date('Y-m-d H:i:s');

$can_vote = $vote_session && $vote_session['status'] === 'active' 
            && $now >= $vote_session['start_time'] 
            && $now <= $vote_session['end_time'];

// Get all positions
$positions = $conn->query("SELECT DISTINCT position FROM candidates")->fetch_all(MYSQLI_ASSOC);

// Already voted check
$voted = [];
$res = $conn->query("SELECT position,candidate_id FROM votes WHERE voter_id=$voter_id");
while($r = $res->fetch_assoc()) $voted[$r['position']] = $r['candidate_id'];

// Handle final submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_submit'])) {
    foreach($_POST as $key => $value){
        if(strpos($key, 'vote_') === 0){
            $position = str_replace('vote_', '', $key);
            $candidate_id = (int)$value;
            // Check if already voted for this position
            $check = $conn->query("SELECT * FROM votes WHERE voter_id=$voter_id AND position='$position'")->num_rows;
            if(!$check){
                $conn->query("INSERT INTO votes (voter_id, position, candidate_id) VALUES ($voter_id, '$position', $candidate_id)");
            }
        }
    }
    header("Location: userDashboard.php?submitted=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Voter Dashboard</title>
<style>
body{font-family:sans-serif;margin:0;padding:0;background:#f4f4f4}
header{background:#4361ee;color:white;padding:15px;text-align:center;font-size:20px;}
.container{max-width:900px;margin:20px auto;padding:10px;background:white;border-radius:8px;}
.position-step{display:none;margin-top:20px;}
.position-step.active{display:block;}
.candidate{display:flex;align-items:center;gap:10px;margin:10px 0;padding:10px;border:1px solid #ccc;border-radius:6px;cursor:pointer;transition:0.3s;}
.candidate.selected{border-color:#4361ee;background:#e0e0ff;}
.candidate img{width:50px;height:50px;border-radius:50%;object-fit:cover;}
button{padding:10px 20px;margin:5px;border:none;border-radius:6px;background:#4361ee;color:white;cursor:pointer;}
button:disabled{background:#ccc;cursor:not-allowed;}
.summary{background:#e9ecef;padding:15px;margin-top:20px;border-radius:6px;}
</style>
</head>
<body>

<header>Voter Dashboard - <?= htmlspecialchars($voter['name']) ?></header>
<div class="container">
<h3>Cast Your Vote</h3>

<?php if(isset($_GET['submitted'])): ?>
    <p style="color:green;">Your votes have been successfully submitted!</p>
<?php endif; ?>

<?php if(!$can_vote): ?>
<p>Voting session inactive.</p>
<?php elseif(empty($positions)): ?>
<p>No positions available to vote.</p>
<?php else: ?>
<form id="voteForm" method="post">
<?php foreach($positions as $i=>$pos): 
    $pos_name = $pos['position'];
?>
<div class="position-step <?= $i==0?'active':'' ?>" data-step="<?=$i?>">
    <h4>Position: <?=$pos_name?></h4>
    <?php
    $cands = $conn->query("SELECT * FROM candidates WHERE position='$pos_name'");
    while($c=$cands->fetch_assoc()): 
        $checked = isset($voted[$pos_name]) && $voted[$pos_name]==$c['id'];
    ?>
    <label class="candidate <?= $checked?'selected':'' ?>">
        <input type="radio" name="vote_<?=$pos_name?>" value="<?=$c['id']?>" <?= $checked?'checked disabled':'' ?> hidden>
        <?php if($c['photo']): ?>
        <img src="uploads/<?= $c['photo'] ?>" alt="<?= $c['name'] ?>">
        <?php else: ?>
        <div style="width:50px;height:50px;background:#4361ee;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;">
            <?= strtoupper(substr($c['name'],0,1)) ?>
        </div>
        <?php endif; ?>
        <div>
            <strong><?= $c['name'] ?></strong><br>
            <?= $c['party'] ?>
        </div>
    </label>
    <?php endwhile; ?>
</div>
<?php endforeach; ?>

<div style="margin-top:15px;">
<button type="button" id="prevBtn" disabled>Previous</button>
<button type="button" id="nextBtn">Next</button>
<button type="button" id="showSummaryBtn">Review Votes</button>
<button type="submit" name="final_submit" id="finalSubmitBtn" style="display:none;">Submit Votes</button>
</div>
</form>

<div id="summary" class="summary" style="display:none;">
<h4>Your Selected Votes:</h4>
<ul id="voteList"></ul>
<button onclick="editVotes()">Edit Votes</button>
<button onclick="submitFinal()" id="confirmSubmitBtn">OK / Submit</button>
</div>
<?php endif; ?>
</div>

<script>
let step=0;
const steps=document.querySelectorAll('.position-step');
const prevBtn=document.getElementById('prevBtn');
const nextBtn=document.getElementById('nextBtn');
const showSummaryBtn=document.getElementById('showSummaryBtn');
const finalSubmitBtn=document.getElementById('finalSubmitBtn');
const voteForm=document.getElementById('voteForm');
const voteList=document.getElementById('voteList');

function showStep(n){
    steps.forEach((s,i)=>s.classList.toggle('active',i===n));
    prevBtn.disabled = n===0;
    nextBtn.style.display = n===steps.length-1?'none':'inline-block';
    showSummaryBtn.style.display = n===steps.length-1?'inline-block':'none';
    finalSubmitBtn.style.display='none';
}

nextBtn.onclick = ()=>{ step++; showStep(step); }
prevBtn.onclick = ()=>{ step--; showStep(step); }

steps.forEach(s=>{
    s.querySelectorAll('input[type=radio]').forEach(r=>{
        r.onchange = ()=>{
            s.querySelectorAll('.candidate').forEach(c=>c.classList.remove('selected'));
            r.closest('.candidate').classList.add('selected');
        }
    })
});

function editVotes(){
    document.getElementById('summary').style.display='none';
    voteForm.style.display='block';
    showStep(0);
}

showSummaryBtn.onclick = ()=>{
    voteList.innerHTML='';
    const formData=new FormData(voteForm);
    formData.forEach((v,k)=>{
        const pos = k.replace('vote_','');
        const selCand = document.querySelector(`input[name="${k}"]:checked`);
        const candName = selCand ? selCand.closest('.candidate').querySelector('strong').innerText : 'Not Selected';
        voteList.innerHTML += `<li>${pos}: ${candName}</li>`;
    });
    voteForm.style.display='none';
    document.getElementById('summary').style.display='block';
}

function submitFinal(){
    finalSubmitBtn.click();
}

showStep(step);
</script>

</body>
</html>
