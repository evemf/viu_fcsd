<?php
/**
 * Multilingual Legal Pages (single post, multi-language fields)
 * - Languages: es, ca, en (editable aquí)
 * - Creates legal pages on theme activation
 * - Metabox para editar traducciones (título, slug, contenido) en la misma pantalla
 * - Rewrites /{lang}/{slug} -> carga la página por su slug del idioma (post meta)
 */

if (!defined('ABSPATH')) { exit; }

/* ---------------------------
 * CONFIG
 * --------------------------- */
if (!defined('VIU_ML_LANGS')) {
  define('VIU_ML_LANGS', 'es,ca,en'); // añade/quita idiomas separados por coma
}
if (!defined('VIU_ML_DEFAULT_LANG')) {
  define('VIU_ML_DEFAULT_LANG', 'es');
}
function viu_ml_get_langs(){
  $langs = array_filter(array_map('trim', explode(',', VIU_ML_LANGS)));
  return $langs ?: ['es','ca','en'];
}

/* ---------------------------
 * CURRENT LANG (from URL or query var)
 * --------------------------- */
function viu_ml_current_lang(){
  // 1) query var ?lang=xx (inyectada por la rewrite)
  $q = get_query_var('viu_lang');
  if ($q) return $q;

  // 2) prefijo de ruta /xx/slug (compatible con subdirectorios)
  $req_path = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'); // p.ej. "ca/politica..."
  $first    = strtok($req_path, '/'); // "ca"
  $langs    = viu_ml_get_langs();

  if (in_array($first, $langs, true)) {
    return $first;
  }

  // 3) fallback
  return VIU_ML_DEFAULT_LANG;
}

/* ---------------------------
 * REWRITE: /{lang}/{slug}
 * --------------------------- */
add_action('init', function(){
  add_rewrite_tag('%viu_lang%', '([a-z]{2})');
  add_rewrite_tag('%viu_mlslug%', '([^/]+)');

  $langs = viu_ml_get_langs();
  $lang_group = '('.implode('|', array_map('preg_quote', $langs)).')';

  // /{lang}/{slug}
  add_rewrite_rule('^'.$lang_group.'/([^/]+)/?$', 'index.php?viu_lang=$matches[1]&viu_mlslug=$matches[2]', 'top');
});

add_filter('query_vars', function($qv){
  $qv[] = 'viu_lang';
  $qv[] = 'viu_mlslug';
  return $qv;
});

add_action('parse_request', function($wp){
  if (!empty($wp->query_vars['viu_mlslug']) && !empty($wp->query_vars['viu_lang'])) {
    $slug = sanitize_title($wp->query_vars['viu_mlslug']);
    $lang = sanitize_key($wp->query_vars['viu_lang']);

    // Buscar página con meta _viu_ml_slug_{lang} = {slug}
    $q = new WP_Query([
      'post_type'      => 'page',
      'post_status'    => 'publish',
      'posts_per_page' => 1,
      'meta_query'     => [[
        'key'   => "_viu_ml_slug_$lang",
        'value' => $slug,
      ]]
    ]);
    if ($q->have_posts()) {
      $q->the_post();
      $wp->query_vars['page_id'] = get_the_ID();
      // Para que WP trate esto como una página
      $wp->query_vars['name'] = get_post_field('post_name', get_the_ID());
      wp_reset_postdata();
    }
  }
});

/* ---------------------------
 * FILTERS: title/content traducidos
 * --------------------------- */
add_filter('the_title', function($title, $post_id){
  if (get_post_type($post_id) !== 'page') return $title;
  $lang = viu_ml_current_lang();
  $t = get_post_meta($post_id, "_viu_ml_title_$lang", true);
  return $t ?: $title;
}, 10, 2);

add_filter('the_content', function($content){
  if (!is_page()) return $content;
  $post_id = get_the_ID();
  $lang = viu_ml_current_lang();
  $c = get_post_meta($post_id, "_viu_ml_content_$lang", true);
  // do_shortcode para que funcionen [org_name], etc.
  return $c ? do_shortcode( wp_kses_post($c) ) : $content;
});

/* ---------------------------
 * Helper: permalink por clave (legal/privacy/cookies/transparency)
 * --------------------------- */
function viu_ml_link_by_key($key, $lang = ''){
  $lang    = $lang ?: viu_ml_current_lang();
  $page_id = (int) get_option("viu_ml_page_$key"); // guardamos IDs al crear
  if (!$page_id || !get_post($page_id)) return home_url('/');
  $slug = get_post_meta($page_id, "_viu_ml_slug_$lang", true);
  if (!$slug) { // fallback al default
    $slug = get_post_meta($page_id, "_viu_ml_slug_" . VIU_ML_DEFAULT_LANG, true);
    $lang = VIU_ML_DEFAULT_LANG;
  }
  if (!$slug) {
    return get_permalink($page_id);
  }
  return home_url('/' . $lang . '/' . $slug);
}

/* ---------------------------
 * METABOX: traducciones por idioma
 * --------------------------- */
add_action('add_meta_boxes', function(){
  add_meta_box(
    'viu_ml_translations',
    __('Traducciones (Título, Slug y Contenido por idioma)', 'viu-fcsd'),
    'viu_ml_metabox_render',
    'page',
    'normal',
    'high'
  );
});

function viu_ml_metabox_render($post){
  wp_nonce_field('viu_ml_save', 'viu_ml_nonce');
  $langs = viu_ml_get_langs();
  $keys  = ['title'=>'Título','slug'=>'Slug','content'=>'Contenido'];

  echo '<style>
    .viu-ml-tabs{display:flex;gap:8px;margin-bottom:8px}
    .viu-ml-tab{padding:6px 10px;background:#eee;border-radius:6px;cursor:pointer;border:1px solid #ddd}
    .viu-ml-tab.is-active{background:#ddd;font-weight:600}
    .viu-ml-panel{display:none}
    .viu-ml-panel.is-active{display:block}
  </style>';

  echo '<div class="viu-ml-tabs">';
  foreach($langs as $i=>$lang){
    echo '<button type="button" class="viu-ml-tab'.(!$i?' is-active':'').'" data-tab="viu-ml-'.esc_attr($lang).'">'.esc_html(strtoupper($lang)).'</button>';
  }
  echo '</div>';

  foreach($langs as $i=>$lang){
    $t = get_post_meta($post->ID, "_viu_ml_title_$lang", true);
    $s = get_post_meta($post->ID, "_viu_ml_slug_$lang", true);
    $c = get_post_meta($post->ID, "_viu_ml_content_$lang", true);

    echo '<div class="viu-ml-panel'.(!$i?' is-active':'').'" id="viu-ml-'.esc_attr($lang).'">';

    printf('<p><label><strong>%s</strong><br><input type="text" name="viu_ml_title_%s" value="%s" class="widefat"></label></p>',
      esc_html($keys['title']).' ('.$lang.')', esc_attr($lang), esc_attr($t)
    );

    printf('<p><label><strong>%s</strong><br><input type="text" name="viu_ml_slug_%s" value="%s" class="widefat" placeholder="sin-espacios-con-guiones"></label></p>',
      esc_html($keys['slug']).' ('.$lang.')', esc_attr($lang), esc_attr($s)
    );

    // Si quieres WYSIWYG: puedes reemplazar por wp_editor() con un ID único por idioma.
    printf('<p><label><strong>%s</strong><br><textarea name="viu_ml_content_%s" rows="10" class="widefat">%s</textarea></label></p>',
      esc_html($keys['content']).' ('.$lang.')', esc_attr($lang), esc_textarea($c)
    );

    echo '</div>';
  }

  // Tabs JS
  echo "<script>
    (function(){
      const tabs=[...document.querySelectorAll('.viu-ml-tab')];
      const panels=[...document.querySelectorAll('.viu-ml-panel')];
      tabs.forEach(t=>t.addEventListener('click', ()=>{
        tabs.forEach(x=>x.classList.remove('is-active'));
        panels.forEach(p=>p.classList.remove('is-active'));
        t.classList.add('is-active');
        document.getElementById(t.dataset.tab).classList.add('is-active');
      }));
    })();
  </script>";
}

add_action('save_post_page', function($post_id){
  if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
  if (!isset($_POST['viu_ml_nonce']) || !wp_verify_nonce($_POST['viu_ml_nonce'], 'viu_ml_save')) return;
  if (!current_user_can('edit_page', $post_id)) return;

  foreach(viu_ml_get_langs() as $lang){
    $t = isset($_POST["viu_ml_title_$lang"])   ? sanitize_text_field($_POST["viu_ml_title_$lang"])   : '';
    $s = isset($_POST["viu_ml_slug_$lang"])    ? sanitize_title($_POST["viu_ml_slug_$lang"])         : '';
    $c = isset($_POST["viu_ml_content_$lang"]) ? wp_kses_post($_POST["viu_ml_content_$lang"])        : '';
    update_post_meta($post_id, "_viu_ml_title_$lang",   $t);
    update_post_meta($post_id, "_viu_ml_slug_$lang",    $s);
    update_post_meta($post_id, "_viu_ml_content_$lang", $c);
  }
});

/* ---------------------------
 * CREATE PAGES ON THEME ACTIVATE (OR IF MISSING)
 * --------------------------- */
function viu_ml_setup_pages(){
  $created = false;
  $create_if_missing = function($key, $defaults) use (&$created){
    $existing = (int) get_option("viu_ml_page_$key");

    if (!$existing || !get_post($existing)) {
      $default_slug = $defaults['es']['slug'] ?? sanitize_title($defaults['es']['title'] ?? '');
      if ($default_slug) {
        $page = get_page_by_path($default_slug);
        if ($page) {
          $existing = $page->ID;
          update_option("viu_ml_page_$key", $existing);
        }
      }
    }

    if ($existing && get_post($existing)) return $existing;

    $post_id = wp_insert_post([
      'post_type'    => 'page',
      'post_status'  => 'publish',
      'post_title'   => $defaults['es']['title']   ?? 'Página',
      'post_content' => $defaults['es']['content'] ?? '',
      'post_name'    => $defaults['es']['slug']    ?? sanitize_title($defaults['es']['title'] ?? 'pagina'),
    ]);

    if ($post_id && !is_wp_error($post_id)){
      foreach($defaults as $lang=>$data){
        update_post_meta($post_id, "_viu_ml_title_$lang",   $data['title']   ?? '');
        update_post_meta($post_id, "_viu_ml_slug_$lang",    $data['slug']    ?? '');
        update_post_meta($post_id, "_viu_ml_content_$lang", wp_kses_post($data['content'] ?? ''));
      }
      update_option("viu_ml_page_$key", $post_id);
      $created = true;
    }
    return $post_id;
  };

  // ===== Contenidos por defecto =====

  $transparency_content = <<<'HTML'
<p>Les cookies són el mitjà tècnic per a la “traçabilitat” i seguiment de la navegació a les pàgines web. Són petits fitxers de text que s’escriuen en l’ordinador de l’Usuari. Aquest mètode té implicacions sobre la privacitat, de manera que la FUNDACIÓ informa que podrà utilitzar cookies amb la finalitat d’elaborar estadístiques d’utilització del lloc web així com per identificar el PC de l’usuari permetent reconèixer en les seves pròximes visites. En tot cas, l’usuari pot configurar el seu navegador per no permetre l’ús de cookies en les seves visites al web site.</p>
<p>A la FUNDACIÓ utilitzem cookies amb l’objectiu de prestar un millor servei i proporcionar-te una millor experiència en la teva navegació. Volem informar-te de manera clara i precisa sobre les cookies que utilitzem, detallant a continuació, què és una galeta, per a què serveix, quina és la seva finalitat i com pots configurar-les o deshabilitar-les si així ho desitja.</p>
<p>De conformitat amb la normativa espanyola que regula l’ús de cookies en relació a la prestació de serveis de comunicacions electròniques, recollida en el Reial decret llei 13/2012, del 30 de Març, l’informem sobre les cookies utilitzades en el lloc web de La FUNDACIÓ (en endavant, el “Lloc Web”) i el motiu del seu ús. Així mateix, LA FUNDACIÓ li informa que en navegar en el Lloc Web vostè està prestant el seu consentiment per a poder utilitzar-les.</p>
<p>Les cookies utilitzades en el nostre lloc web, poden ser pròpies i de tercers, i ens permeten emmagatzemar i accedir a informació relativa a l’idioma, el tipus de navegador utilitzat, i altres característiques generals predefinides per l’usuari, així com seguir i analitzar l’activitat que du a terme, amb l’objecte d’introduir millores i prestar els nostres serveis d’una manera més eficient i personalitzada.</p>
<p>La utilització de les cookies ofereix nombrosos avantatges en la prestació de serveis de la societat de la informació, ja que, entre altres: (i) facilita a l’usuari la navegació en el Lloc Web i l’accés als diferents serveis que ofereix; (Ii) evita a l’usuari configurar les característiques generals predefinides cada vegada que accedeix al Lloc Web; (Iii) afavoreix la millora del funcionament i dels serveis prestats a través del lloc web després del corresponent anàlisi de la informació obtinguda per mitjà de les cookies instal·lades.</p>
<p>No obstant això, pot configurar el seu navegador, acceptant o rebutjant totes les galetes, o bé sel.leccionant aquelles, instal.lació de les quals pugui admetre o no vulgui admetre, seguint un dels següents procediments, i depenent del navegador utilitzat:</p>
<p>Google Chrome (al Menú Eines): Configuració&gt; Mostra opcions avançades&gt; Privadesa (Configuració de contingut)&gt; Galetes Més informació: http://support.google.com/chrome/bin/answer.py?hl=es&amp;answer=95647<br/>
Microsoft Internet Explorer (al Menú Eines): Opcions d’Internet&gt; Privadesa&gt; Avançada<br/>
Més informació:<br/>
http://windows.microsoft.com/es-es/windows7/how-to-manage-cookies-in-internet-explorer-9<br/>
Firefox: Opcions&gt; Privadesa&gt; Galetes<br/>
Més informació: http://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-que-los-sitios-we<br/>
Safari, iPad i iPhone: Preferències&gt; Privadesa<br/>
Més informació: http://support.apple.com/kb/ph5042</p>
<p>Disponibilitat de la Pàgina<br/>
La FUNDACIÓ no garanteix la inexistència d’interrupcions o errors en l’accés a la Pàgina i als seus Continguts, encara que aquests es trobin actualitzats, encara que desplegarà els seus millors esforços per a, si s´escau, evitar-los, resoldre’ls o actualitzar-los. Per tant, LA FUNDACIÓ no es responsabilitza dels danys o perjudicis de qualsevol tipus produïts en l’Usuari final que portin causa d’errors o desconnexions de les xarxes de telecomunicacions que produeixin la suspensió, cancel·lació o interrupció del servei del Portal durant la seva prestació o amb caràcter previ.</p>
<p>La FUNDACIÓ exclou, amb les excepcions previstes en la legislació vigent, qualsevol responsabilitat pels danys i perjudicis de tota naturalesa que puguin deure´s a la falta de disponibilitat, continuïtat o qualitat del funcionament de la Pàgina i dels Continguts, així com al no compliment de l´expectativa d’utilitat que els usuaris haguessin pogut atribuir a la Pàgina i als Continguts.</p>
<p>La funció dels Hiperenllaços que apareixen en aquesta web és exclusivament la d’informar a l’usuari sobre l’existència d’altres web que contenen informació sobre la matèria. Aquests Hiperenllaços no constitueixen suggeriment ni recomanació.</p>
<p>La FUNDACIÓ no es fa responsable dels continguts d’aquestes pàgines enllaçades, del funcionament o utilitat dels Hiperenllaços ni del resultat d’aquests enllaços, ni garanteix l’absència de virus o altres elements en els mateixos que puguin produir alteracions en el sistema informàtic (maquinari i programari), els documents o els fitxers de l’usuari, excloent qualsevol responsabilitat pels danys de qualsevol classe causats a l’usuari per aquest motiu.</p>
<p>L’accés a la Pàgina no implica l’obligació per part de LA FUNDACIÓ de controlar l’absència de virus, cucs o qualsevol altre element informàtic nociu. Correspon a l’Usuari, en tot cas, la disponibilitat d’eines adequades per a la detecció i desinfecció de programes informàtics nocius, per tant, LA FUNDACIÓ no es fa responsable dels possibles errors de seguretat que es puguin produir durant la prestació del servei de la pàgina, ni dels possibles danys que puguin causar al sistema informàtic de l’usuari o de tercers (hardware i software), així com als seus fitxers o documents emmagatzemats en el mateix sistema informàtic, com a conseqüència de la presència de virus a l’ordinador de l’usuari amb el que ha establert la connexió als serveis i continguts del web, d’un mal funcionament del navegador o de l’ús de versions no actualitzades del mateix.</p>
<p>Qualitat de la Pàgina<br/>
Atès l’entorn dinàmic i canviant tant de la informació com dels serveis que es posen a disposició de l´usuari per mitjà de la Pàgina, LA FUNDACIÓ realitza el seu millor esforç, però no garanteix la completa veracitat, exactitud, fiabilitat, utilitat i/o actualitat dels continguts.</p>
<p>La informació continguda en les pàgines que componen aquest Portal només té caràcter informatiu, consultiu, divulgatiu i publicitari. En cap cas ofereixen ni tenen caràcter de compromís vinculant o contractual.</p>
<p>Limitació de responsabilitat<br/>
L’FUNDACIÓ exclou tota responsabilitat per les decisions que l’Usuari final pugui prendre tot basant-se en aquesta informació, així com pels possibles errors tipogràfics que puguin contenir els documents i gràfics de la Pàgina. La informació està sotmesa a possibles canvis diaris sense previ avís del seu contingut per ampliació, millora, correcció o actualització dels continguts.</p>
<p>Notificacions<br/>
Totes les notificacions i comunicacions que realitzi La Fundació a través de qualsevol mitjà es consideraran eficaces a tots els efectes.</p>
<p>Disponibilitat de los Continguts<br/>
La prestació del servei de la Pàgina i dels Continguts té, en principi, durada indefinida. La FUNDACIÓ, no obstant això, queda autoritzada per donar per acabada o suspendre la prestació del servei de la pàgina i/o de qualsevol dels Continguts en qualsevol moment. Quan això sigui raonablement possible, LA FUNDACIÓ advertirà prèviament la fi o suspensió de la Pàgina.</p>
<p>Protecció de Dades de Caràcter Personal<br/>
La FUNDACIÓ és conscient de la importància de la privacitat de les dades de caràcter personal i per això, ha implementat una política de tractament de dades orientada a proveir la màxima seguretat en l’ús i recollida de les mateixes, garantint el compliment de la normativa vigent en la matèria i configurant aquesta política com un dels pilars bàsics en les línies d’actuació de l’entitat.</p>
<p>Durant la navegació a través del web http://www.fcsd.org/es és possible que es sol·licitin dades de caràcter personal a través de diferents formularis disposats a l´efecte. Aquestes dades formaran part dels pertinents fitxers en funció de la finalitat determinada i concreta que motiva la recaptació de les mateixes.</p>
<p>D’aquesta manera, la informació particular de cada tractament de dades s’aportarà al costat de cada formulari web, sent comuna a tots ells el responsable del tractament: LA FUNDACIÓ domiciliada a C / Comte Borrell, 201-203, entresòl, 08029, Barcelona, ​​així com el lloc i forma d’exercici dels drets d’accés, rectificació, supressió, portabilitat de les seves dades, així com de limitació o oposició al seu tractament, s’ha de formalitzar mitjançant una comunicació escrita a l’adreça indicada anteriorment incloent còpia del DNI o document identificatiu equivalent.</p>
<p>En el cas que aporti les seves dades a través d’un missatge de correu electrònic, el mateix missatge formarà part d’un fitxer la finalitat del qual serà la gestió de la sol·licitud o comentari que ens realitza, essent aplicables la resta d’extrems indicats en el paràgraf anterior.</p>
<p>Així mateix, les condicions generals de contractació dels serveis de LA FUNDACIÓ contenen les característiques i naturalesa del tractament de les dades que seran gestionades per la mateixa Fundació en el cas que es contracti qualsevol dels serveis.</p>
<p>D’altra banda, la FUNDACIÓ ha implantat les mesures tècniques i organitzatives necessàries per a evitar la pèrdua, mal ús, alteració, accés no autoritzat i robatori de les Dades Personals que els interessats poguessin facilitar com a conseqüència de l’accés a les diferents seccions del lloc web http://www.fcsd.org/es aplicant les mesures de seguretat previstes en el Reglament (UE) 2016/679 del Parlament Europeu i del Consell, del 27 d’abril de 2016, relatiu a la protecció de les persones físiques pel que fa al tractament de dades personals i a la lliure circulació d’aquestes dades (Reglament General de Protecció de dades – RGPD).</p>
<p>Jurisdicció<br/>
Per a les qüestions vinculades a la interpretació, aplicació i compliment d’aquest Avís Legal, així com de les reclamacions que puguin derivar-se del seu ús, totes les parts que intervenen es sotmeten als Jutges i Tribunals de Barcelona, ​​renunciant de forma expressa a qualsevol altre fur que pogués correspondre’ls.</p>
<p>Legislació aplicable<br/>
L´Avís Legal es regeix per la llei Espanyola</p>
<p>Copyright© LA FUNDACIÓ.<br/>
Reservats tots els drets d’autor per les lleis i tractats internacionals de propietat intel·lectual. Queda expressament prohibida la seva còpia, reproducció o difusió, total o parcial, a través de qualsevol mitjà.</p>
HTML;

  $pages = [
    'legal' => [
      'es'=>[
        'title'=>'Aviso legal',
        'slug'=>'aviso-legal',
        'content'=> <<<'HTML'
<h2>Información general</h2>
<p>En cumplimiento del artículo 10 de la Ley 34/2002, de Servicios de la Sociedad de la Información y Comercio Electrónico, se informa que [org_name], con CIF [org_cif] y domicilio en <span class="addr">[org_address]</span>, es titular del sitio web <span class="url">[org_website]</span>.</p>
<h2>Condiciones de uso</h2>
<p>El acceso y uso del sitio atribuye la condición de persona usuaria e implica la aceptación de este aviso legal.</p>
<h2>Propiedad intelectual</h2>
<p>Los contenidos de este sitio, salvo indicación en contrario, son titularidad de [org_name]. Queda prohibida su reproducción sin autorización.</p>
<h2>Responsabilidad</h2>
<p>[org_name] no se responsabiliza del uso que se haga de la información publicada ni de los daños que puedan derivarse de dicho uso.</p>
HTML
      ],
      'ca'=>[
        'title'=>'Avís legal',
        'slug'=>'avis-legal',
        'content'=> <<<'HTML'
<h2>Informació general</h2>
<p>En compliment de l'article 10 de la Llei 34/2002, de Serveis de la Societat de la Informació i Comerç Electrònic, s'informa que [org_name], amb CIF [org_cif] i domicili a <span class="addr">[org_address]</span>, és titular del lloc web <span class="url">[org_website]</span>.</p>
<h2>Condicions d'ús</h2>
<p>L'accés i ús del lloc atribueix la condició de persona usuària i implica l'acceptació d'aquest avís legal.</p>
<h2>Propietat intel·lectual</h2>
<p>Els continguts d'aquest lloc, llevat indicació en contra, són titularitat de [org_name]. Es prohibeix la seva reproducció sense autorització.</p>
<h2>Responsabilitat</h2>
<p>[org_name] no es fa responsable de l'ús que es faci de la informació publicada ni dels danys que en puguin derivar.</p>
HTML
      ],
      'en'=>[
        'title'=>'Legal Notice',
        'slug'=>'legal-notice',
        'content'=> <<<'HTML'
<h2>General information</h2>
<p>In accordance with article 10 of Law 34/2002 on Information Society Services, [org_name], tax ID [org_cif] and registered address at <span class="addr">[org_address]</span>, is the owner of the website <span class="url">[org_website]</span>.</p>
<h2>Terms of use</h2>
<p>Access to and use of this site attributes the status of user and implies acceptance of this legal notice.</p>
<h2>Intellectual property</h2>
<p>Unless otherwise indicated, the contents of this site belong to [org_name]. Reproduction without authorization is prohibited.</p>
<h2>Liability</h2>
<p>[org_name] is not responsible for the use made of the information on this site nor for any damages arising from such use.</p>
HTML
      ],
    ],

    'privacy' => [
      'ca'=>[
        'title'=>'Política de privacitat',
        'slug'=>'politica-de-privacitat',
        'content'=> <<<'HTML'
<h2>Responsable de les dades</h2>
<p>[org_name], amb CIF [org_cif] i domicili a <span class="addr">[org_address]</span>, és responsable del tractament de les dades recollides a través de <span class="url">[org_website]</span>. Contacte del DPD: <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>
<h2>Finalitat i legitimació</h2>
<p>Les dades es tractaran per atendre les consultes, gestionar els serveis sol·licitats i enviar informació relacionada. La base jurídica és el consentiment i, si escau, l'execució d'un contracte.</p>
<h2>Destinataris</h2>
<p>No es cediran dades a tercers excepte obligació legal o encàrrec de tractament.</p>
<h2>Drets</h2>
<p>Pot exercir els drets d'accés, rectificació, supressió, oposició, limitació i portabilitat mitjançant un correu a <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>
<p><em>Política actualitzada a [policy_date].</em></p>
HTML
      ],
      'es'=>[
        'title'=>'Política de privacidad',
        'slug'=>'politica-de-privacidad',
        'content'=> <<<'HTML'
<h2>Responsable de los datos</h2>
<p>[org_name], con CIF [org_cif] y domicilio en <span class="addr">[org_address]</span>, es responsable del tratamiento de los datos recogidos a través de <span class="url">[org_website]</span>. Contacto del DPO: <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>
<h2>Finalidad y legitimación</h2>
<p>Los datos se tratarán para atender sus consultas, gestionar los servicios solicitados y enviar información relacionada. La base jurídica es su consentimiento y, en su caso, la ejecución de un contrato.</p>
<h2>Destinatarios</h2>
<p>No se cederán datos a terceros salvo obligación legal o encargo de tratamiento.</p>
<h2>Derechos</h2>
<p>Puede ejercer los derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad enviando un correo a <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>
<p><em>Política actualizada a [policy_date].</em></p>
HTML
      ],
      'en'=>[
        'title'=>'Privacy Policy',
        'slug'=>'privacy-policy',
        'content'=> <<<'HTML'
<h2>Data controller</h2>
<p>[org_name], tax ID [org_cif] and address at <span class="addr">[org_address]</span>, is responsible for processing the data collected through <span class="url">[org_website]</span>. DPO contact: <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>
<h2>Purpose and legal basis</h2>
<p>Data are processed to handle enquiries, manage requested services and send related information. The legal basis is consent and, where applicable, performance of a contract.</p>
<h2>Recipients</h2>
<p>Data will not be disclosed to third parties except under legal obligation or data processor agreements.</p>
<h2>Rights</h2>
<p>You may exercise your rights of access, rectification, erasure, objection, restriction and portability by emailing <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>
<p><em>Policy updated on [policy_date].</em></p>
HTML
      ],
    ],

    'cookies' => [
      'es'=>[
        'title'=>'Política de cookies',
        'slug'=>'politica-de-cookies',
        'content'=> <<<'HTML'
<h2>¿Qué son las cookies?</h2>
<p>Las cookies son pequeños archivos que se almacenan en su dispositivo cuando visita un sitio web.</p>
<h2>Cookies utilizadas</h2>
<p>Este sitio utiliza cookies propias y de terceros con fines técnicos, de personalización y análisis anónimo.</p>
<h2>Cómo gestionar las cookies</h2>
<p>Puede configurar su navegador para permitir, bloquear o eliminar las cookies instaladas. Al continuar navegando acepta su uso.</p>
HTML
      ],
      'ca'=>[
        'title'=>'Política de galetes',
        'slug'=>'politica-de-galetes',
        'content'=> <<<'HTML'
<h2>Què són les galetes?</h2>
<p>Les galetes són fitxers petits que s'emmagatzemen al dispositiu quan visita un lloc web.</p>
<h2>Galetes utilitzades</h2>
<p>Aquest lloc utilitza galetes pròpies i de tercers amb finalitats tècniques, de personalització i d'anàlisi anònima.</p>
<h2>Com gestionar les galetes</h2>
<p>Podeu configurar el vostre navegador per permetre, bloquejar o eliminar les galetes instal·lades. Si continueu navegant, accepteu el seu ús.</p>
HTML
      ],
      'en'=>[
        'title'=>'Cookies Policy',
        'slug'=>'cookies-policy',
        'content'=> <<<'HTML'
<h2>What are cookies?</h2>
<p>Cookies are small files stored on your device when visiting a website.</p>
<h2>Cookies used</h2>
<p>This site uses first-party and third-party cookies for technical purposes, personalisation and anonymous analytics.</p>
<h2>Managing cookies</h2>
<p>You can configure your browser to allow, block or delete cookies. Continuing to browse constitutes acceptance of their use.</p>
HTML
      ],
    ],

    'transparency' => [
      'es'=>[
        'title'=>'Transparencia',
        'slug'=>'transparencia',
        'content'=> <<<'HTML'
<h2>Compromiso de transparencia</h2>
<p>[org_name] pone a disposición de la ciudadanía información actualizada sobre su actividad, organización y recursos económicos.</p>
<h2>Documentación</h2>
<p>En esta página se publicarán las memorias, cuentas anuales, informes de auditoría y otros documentos de interés.</p>
HTML
      ],
      'ca'=>[
        'title'=>'Transparència',
        'slug'=>'transparencia',
        'content'=>$transparency_content,
      ],
      'en'=>[
        'title'=>'Transparency',
        'slug'=>'transparency',
        'content'=> <<<'HTML'
<h2>Commitment to transparency</h2>
<p>[org_name] provides citizens with up-to-date information about its activity, organisation and financial resources.</p>
<h2>Documentation</h2>
<p>This page will publish annual reports, financial statements, audit reports and other relevant documents.</p>
HTML
      ],
    ],
  ];

  foreach($pages as $key=>$defs){ $create_if_missing($key, $defs); }

  if ($created) {
    flush_rewrite_rules();
  }
}
add_action('after_switch_theme', 'viu_ml_setup_pages');
add_action('init', 'viu_ml_setup_pages');

/* ---------------------------
 * FOOTER HELPERS
 * --------------------------- */
function viu_ml_footer_links(){
  $items = [
    'legal'        => __('Aviso legal','viu-fcsd'),
    'privacy'      => __('Política de privacidad','viu-fcsd'),
    'cookies'      => __('Política de cookies','viu-fcsd'),
    'transparency' => __('Transparencia','viu-fcsd'),
  ];
  echo '<ul class="c-footer__legal">';
  foreach($items as $key=>$label){
    $url = esc_url( viu_ml_link_by_key($key) );
    printf('<li><a href="%s">%s</a></li>', $url, esc_html($label));
  }
  echo '</ul>';
}
