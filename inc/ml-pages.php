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
  $lang = $lang ?: viu_ml_current_lang();
  $page_id = (int) get_option("viu_ml_page_$key"); // guardamos IDs al crear
  if (!$page_id) return home_url('/');
  $slug = get_post_meta($page_id, "_viu_ml_slug_$lang", true);
  if (!$slug) { // fallback al default
    $slug = get_post_meta($page_id, "_viu_ml_slug_".VIU_ML_DEFAULT_LANG, true);
    $lang = VIU_ML_DEFAULT_LANG;
  }
  return home_url('/'.$lang.'/'.$slug);
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
 * CREATE PAGES ON THEME ACTIVATE
 * --------------------------- */
add_action('after_switch_theme', function(){

  $create_if_missing = function($key, $defaults){
    $existing = (int) get_option("viu_ml_page_$key");
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
    }
    return $post_id;
  };

  // ===== Contenidos por defecto =====
  $pages = [
    'legal' => [
      'es'=>['title'=>'Aviso legal','slug'=>'aviso-legal','content'=>'<h2>Aviso legal</h2><p>Contenido legal en español. Personaliza y traduce desde el metabox.</p>'],
      'ca'=>['title'=>'Avís legal','slug'=>'avis-legal','content'=>'<h2>Avís legal</h2><p>Contingut legal en català. Personalitza i tradueix des del metabox.</p>'],
      'en'=>['title'=>'Legal Notice','slug'=>'legal-notice','content'=>'<h2>Legal Notice</h2><p>Starter content in English. Customize and translate in the metabox.</p>'],
    ],

    'privacy' => [
      'ca'=>[
        'title'=>'Política de privacitat',
        'slug'=>'politica-de-privacitat',
        'content'=> <<<'HTML'
<h2>1. RESPONSABLE DE LES DADES</h2>
<p>[org_name] (d’ara endavant, “LA FUNDACIÓ”) amb CIF [org_cif] i domicili a <span class="addr">[org_address]</span>, és el responsable d’aquest lloc web (<span class="url">[org_website]</span>) i de totes les dades recaptades en ell.</p>
<p>LA FUNDACIÓ és conscient de la importància de la privacitat de les dades de caràcter personal i per això, ha implementat una política de tractament de dades orientada a proveir la màxima seguretat en l’ús i recollida d’aquests, garantint el compliment de la normativa vigent en la matèria i configurant aquesta política com un dels pilars bàsics en les línies d’actuació de l’organització. En aquest sentit, volem garantir-li que estem compromesos amb la protecció de les seves dades pel que hem adoptat les mesures necessàries per a evitar possibles danys o pèrdues de les dades.</p>
<p>Aquesta Política de Privacitat té per objecte facilitar-li la informació necessària en relació amb el tractament que realitzem de les dades recaptades així com els drets que li assisteixen, en virtut del compliment per part de LA FUNDACIÓ del Reglament (UE) 2016/679 (RGPD) i altra normativa relativa a la protecció de dades personals.</p>
<p>LA FUNDACIÓ ha designat un Delegat de Protecció de Dades (DPD) i pot contactar-hi a través del correu electrònic <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>
<p>Entenem que si visita el nostre lloc web, accepta la present política de privacitat. En cas contrari, li preguem que no utilitzi el nostre lloc web.</p>

<h2>2. QUINES DADES RECOLLIM I PER A QUÈ?</h2>
<p>LA FUNDACIÓ recull la IP a través de cookies (veure la Política de cookies) per a realitzar anàlisis estadístiques i monitoratge del funcionament del lloc web. Aquesta informació es tracta de manera que no permet identificar la persona i la finalitat és monitorar el correcte funcionament de la pàgina i saber, per exemple, quins productes, serveis, apartats o notícies han agradat més.</p>
<p>Durant la navegació és possible que se sol·licitin dades personals a través de diferents formularis. Aquestes dades formaran part dels pertinents tractaments en funció de la finalitat determinada.</p>
<p>La informació particular de cada tractament s’aportarà al costat de cada formulari web, sent comú a tots ells el responsable del tractament indicat anteriorment i el lloc i forma d’exercici dels drets d’accés, rectificació, supressió, portabilitat de dades, així com de limitació o oposició al tractament, mitjançant comunicació escrita a l’adreça indicada o a <a href="mailto:[dpo_email]">[dpo_email]</a>, incloent còpia del DNI o document identificatiu equivalent.</p>
<p>Si ens aporta dades via correu electrònic, aquestes s’utilitzaran per gestionar la seva sol·licitud o comentari, essent aplicable la resta d’extrems indicats.</p>
<p>Les condicions generals de contractació dels serveis de LA FUNDACIÓ contenen les característiques i naturalesa del tractament de dades quan contracti qualsevol servei.</p>

<h3>Formulari de Contacte</h3>
<p><strong>Dades:</strong> Identificatives i de contacte.</p>
<p><strong>Finalitats:</strong> Tramitar la consulta/sol·licitud; enviament d’informació comercial sobre activitats relacionades amb la Fundació i/o felicitacions.</p>
<p><strong>Legitimació:</strong> Interès legítim (gestió de consulta) i consentiment/interès legítim (publicitat; sempre amb opció de retirar-lo).</p>

<h3>Formulari de CV</h3>
<p><strong>Dades:</strong> Identificatives i de contacte, dades personals, socials, acadèmiques, professionals i laborals.</p>
<p><strong>Finalitats:</strong> Gestió de la seva participació en processos de selecció.</p>
<p><strong>Legitimació:</strong> Consentiment.</p>

<h3>Jornades i Esdeveniments</h3>
<p><strong>Dades:</strong> Identificatives i de contacte.</p>
<p><strong>Finalitats:</strong> Gestió de la inscripció; enviament d’informació comercial relacionada; captació i publicació d’imatges i/o veu amb consentiment.</p>
<p><strong>Legitimació:</strong> Execució de contracte (condicions d’inscripció) i consentiment/interès legítim en supòsits concrets.</p>

<h3>Newsletter</h3>
<p><strong>Finalitats:</strong> Gestionar la subscripció voluntària i l’enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Execució del servei i consentiment/interès legítim quan correspongui.</p>

<h3>Formació</h3>
<p><strong>Finalitats:</strong> Gestió d’inscripcions; enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Execució de contracte i consentiment/interès legítim quan correspongui.</p>

<h3>Donatius</h3>
<p><strong>Finalitats:</strong> Gestió de donatius; enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Execució de contracte i consentiment/interès legítim quan correspongui.</p>

<h3>Soci</h3>
<p><strong>Finalitats:</strong> Gestió de socis; enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Execució de contracte i consentiment/interès legítim quan correspongui.</p>

<h3>Voluntari</h3>
<p><strong>Finalitats:</strong> Gestió de voluntaris; enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Execució de contracte i consentiment/interès legítim quan correspongui.</p>

<h3>Compra online</h3>
<p><strong>Finalitats:</strong> Gestionar la seva sol·licitud de compra; enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Execució de contracte i consentiment/interès legítim quan correspongui.</p>

<h3>Petició de cita mèdica</h3>
<p><strong>Finalitats:</strong> Gestionar la seva sol·licitud de cita; enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Gestió de la sol·licitud i consentiment/interès legítim quan correspongui.</p>

<h3>Subscripció ABC21</h3>
<p><strong>Finalitats:</strong> Gestionar la seva inscripció; enviament d’informació relacionada.</p>
<p><strong>Legitimació:</strong> Execució de contracte (bases legals) i consentiment/interès legítim quan correspongui.</p>

<h2>3. COMUNIQUEM LES DADES A ALGUN TERCER?</h2>
<p>Només comunicarem dades quan existeixi base legal (obligació legal, execució de contracte, consentiment). Comptem amb encarregats del tractament que accedeixen a dades únicament per prestar el servei contractat (per exemple, serveis informàtics o de màrqueting), amb el corresponent acord de tractament.</p>

<h2>4. QUANT TEMPS CONSERVAREM LES SEVES DADES?</h2>
<p>Conservarem les dades el temps necessari per a la finalitat recollida. Per a enviaments comercials, mentre no s’hi oposi o retiri el consentiment. Després, les dades quedaran bloquejades a disposició d’autoritats durant els terminis legals de prescripció.</p>

<h2>5. MESURES DE SEGURETAT</h2>
<p>Hem implantat mesures tècniques i organitzatives adequades d’acord amb el RGPD i segons anàlisi de riscos per evitar pèrdua, mal ús, alteració o accés no autoritzat.</p>

<h2>6. TRANSFERÈNCIES INTERNACIONALS</h2>
<p>No es realitzen transferències internacionals fora de la UE o sense un nivell adequat declarat per la Comissió Europea.</p>

<h2>7. ENLLAÇOS A ALTRES PÀGINES</h2>
<p>Aquesta política s’aplica al nostre lloc web. Si accedeix a webs de tercers mitjançant enllaços, consulti les seves polítiques de privacitat.</p>

<h2>8. DRETS</h2>
<p>Pot exercir els drets d’accés, rectificació, oposició, supressió, limitació, portabilitat i a no ser objecte de decisions automatitzades (inclosa la perfilació), així com retirar el consentiment, mitjançant escrit a l’adreça del responsable o a <a href="mailto:[dpo_email]">[dpo_email]</a>. També pot presentar reclamació davant l’Agència Espanyola de Protecció de Dades (www.agpd.es).</p>

<h2>9. MENORS</h2>
<p>Els nostres llocs estan dirigits a majors de 18 anys. En emplenar formularis, garanteix ser major d’edat. Podem sol·licitar prova d’edat. Recomanem als tutors supervisar l’activitat en línia dels menors.</p>

<h2>10. CONTACTE</h2>
<p>Per a qüestions sobre protecció de dades, escrigui a <a href="mailto:[dpo_email]">[dpo_email]</a>.</p>

<h2>11. ACTUALITZACIONS</h2>
<p>Qualsevol canvi en aquesta política es comunicarà i serà vàlid des del moment de la seva publicació.</p>

<p><em>Política de Privacitat actualitzada a data [policy_date].</em></p>
HTML
      ],
      'es'=>[
        'title'=>'Política de privacidad',
        'slug'=>'politica-de-privacidad',
        'content'=>'<h2>Política de privacidad</h2><p>Versión base generada por el tema. Edita/añade el contenido en español en esta misma página usando el metabox de idiomas. Los datos dinámicos: [org_name], [org_cif], [org_address], [org_website], [dpo_email], [policy_date].</p>'
      ],
      'en'=>[
        'title'=>'Privacy Policy',
        'slug'=>'privacy-policy',
        'content'=>'<h2>Privacy Policy</h2><p>Base version generated by the theme. Edit/add English content on this same page using the language metabox. Dynamic data via shortcodes: [org_name], [org_cif], [org_address], [org_website], [dpo_email], [policy_date].</p>'
      ],
    ],

    'cookies' => [
      'es'=>['title'=>'Política de cookies','slug'=>'politica-de-cookies','content'=>'<h2>Cookies</h2><p>Contenido base. Personaliza y traduce desde el metabox.</p>'],
      'ca'=>['title'=>'Política de galetes','slug'=>'politica-de-galetes','content'=>'<h2>Galetes</h2><p>Contingut base. Personalitza i tradueix des del metabox.</p>'],
      'en'=>['title'=>'Cookies Policy','slug'=>'cookies-policy','content'=>'<h2>Cookies</h2><p>Starter content. Customize and translate in the metabox.</p>'],
    ],

    'transparency' => [
      'es'=>['title'=>'Transparencia','slug'=>'transparencia','content'=>'<h2>Transparencia</h2><p>Contenido base. Personaliza y traduce desde el metabox.</p>'],
      'ca'=>['title'=>'Transparència','slug'=>'transparencia','content'=>'<h2>Transparència</h2><p>Contingut base. Personalitza i tradueix des del metabox.</p>'],
      'en'=>['title'=>'Transparency','slug'=>'transparency','content'=>'<h2>Transparency</h2><p>Starter content. Customize and translate in the metabox.</p>'],
    ],
  ];

  foreach($pages as $key=>$defs){ $create_if_missing($key, $defs); }

  // Activar reglas /{lang}/{slug}
  flush_rewrite_rules();
});

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
