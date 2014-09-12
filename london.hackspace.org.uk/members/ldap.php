<? 
$page = 'ldap';
$extra_head = '
        <script type="text/javascript" src="/javascript/md4.js"></script>
        <script type="text/javascript" src="/javascript/sha1.js"></script>
        <script type="text/javascript" src="/javascript/enc-base64.js"></script>
';
require( '../header.php' );

if (!$user) {
    fURL::redirect('/login.php?forward=/members/wiki.php');
}
?>
<h2>LDAP Account</h2>

<script>

function calculateHashes()
{
	/* this is:
	
	NThash=MD4(UTF-16-LE(password))

	- or -

	echo -n "fish1234" | iconv -t utf-16le | openssl md4

	It does appear that hex_md4 and friends do do utf-16le.
	
	*/
	var nt_password = document.getElementById("nt_password").value;
	var NTLMHash = "";
	NTLMHash = hex_md4(nt_password);

	document.getElementById("ldapnthash").value = NTLMHash;

	/* SSHA infos:
	
		http://www.openldap.org/faq/data/cache/347.html
		
		https://code.google.com/p/crypto-js/
	
	 */
	var ssha_password = document.getElementById("ssha_password").value;

	var salt = CryptoJS.lib.WordArray.random(4);
	var shahash = CryptoJS.algo.SHA1.create();
	shahash.update(ssha_password);
	shahash.update(salt);
	
	var hash = shahash.finalize();
	
	hash = hash.concat(salt);
	
	var out = "{SSHA}" + CryptoJS.enc.Base64.stringify(hash);
	
	document.getElementById("ldapsshahash").value = out;
	var form = document.getElementById("ldapform");
	form.submit();
}

</script>

<?

if($user->isMember()) {
    $email = $user->getEmail();

    // Link or unlink a user.
    if( array_key_exists( 'create', $_POST ) ) {
        fRequest::validateCSRFToken($_POST['token']);
        try {
            $validator = new fValidation();
            $validator->addRequiredFields( 'ldapuser', 'ldapnthash', 'ldapsshahash' );
            $validator->validate();

            // Attempt account creation and promotion.
            $username = escapeshellarg( $_POST['ldapuser'] );
            $nthash = escapeshellarg( $_POST['ldapnthash'] );
            $sshahash = escapeshellarg( $_POST['ldapsshahash'] );

//            $success = trim( shell_exec( "unset REQUEST_METHOD;php {$path}maintenance/createAndPromote.php --globals $username $password 2>&1 1> /dev/null" ) );
/*            if( $success === 'account exists.' ) {
                throw new fValidationException( '<p>An account on the wiki with that username already exists.</p>' );
            } elseif( $success !== '' ) {
                throw new fValidationException( '<p>An unknown error ocurred while creating that wiki account, please contact IRC.</p>' );
            } else {
                // Update e-mail address for created user.
//                $username = $_POST['username'];
//                $db->translatedQuery( 'UPDATE mwuser SET user_email=%s,user_email_authenticated=%s WHERE user_name=%s', $email, date( 'Y-m-d H:i:s' ), $username );
            }*/
        } catch (fValidationException $e) {
            $error = $e->getMessage();
        } catch (fSQLException $e) {
            $error = "<p>An unexpected LDAP error occurred, please contact IRC.</p>";
            trigger_error( $e->getMessage() );
        }
    }
?>
    <p>As a member of London Hackspace you can have an entry in our <a href="https://wiki.london.hackspace.org.uk/view/LDAP">LDAP database</a></a>.</p>

    <p>This will allow you to log into our vairous servers and use a <a href="https://spacefed.net/wiki/index.php/SpaceFED">federated wifi system</a> thats linked to multiple hackspaces accross the globe.</p>

    <p>There are many systems that can talk to an ldap database, we may expand this to include many other services in the future.</p>

    <p>There are 2 password fields below beacause the NTPassword hash is very weak. Unfortunatly you have to use it for spacefed. Note that it will only be used for spacefed!</p>
    
    <p>It's probably a good idea for the passwords here to be different from the password for this website.</p>

    <!--
        irc_nick is null for all users and has no ui to edit 
        nickname is for the door announcer.
    -->

    <input id="username"   type=text size=32><label for="usernam">LDAP Username</label><br />
    <input id="ssha_password" type=password size=32><label for="ssha_password">SSHA Password</label><br />
    <input id="nt_password" type=password size=32><label for="nt_password">NT Password</label><br />

    <button onclick="calculateHashes()">Update LDAP account</button>

    <form method="POST" style="margin: 0;" id="ldapform">
    <input type="hidden" name="token" value="<?=fRequest::generateCSRFToken()?>" />
    <input type="hidden" name="ldapuser" value="" />
    <input type="hidden" name="ldapnthash" value="" />
    <input type="hidden" name="ldapsshahash" value="" />
    <input type="hidden" name="create" value="create" />
    </form>
<? } else { ?>
    <p>You must be a member to use this page.</p>
<?php } 

require('../footer.php'); ?>
</body>
</html>
