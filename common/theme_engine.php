<?php
// ==========================================
// 🎨 DUKAANOVA ADVANCED THEMING ENGINE
// ==========================================
$theme = $seller['theme'] ?? 'dawn';

// DEFAULT: STANDARD LAYOUT
$layout_style = 'standard';
$font_import = "https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap";
$font_family = "'Inter', sans-serif";
$bg_color = "bg-gray-50";
$text_color = "text-gray-900";
$text_muted = "text-gray-500";
$card_bg = "bg-white";
$border_class = "border-gray-200";
$btn_class = "bg-black hover:bg-gray-800 text-white rounded-xl shadow-md";
$input_bg = "bg-gray-50";
$variant_active_class = "peer-checked:bg-black peer-checked:text-white peer-checked:border-black";

// STANDARD
if ($theme === 'ocean') { 
    $font_import = "https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap"; $font_family = "'Nunito', sans-serif"; $btn_class = "bg-blue-600 hover:bg-blue-700 text-white rounded-full shadow-xl"; $variant_active_class = "peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600"; 
} 
elseif ($theme === 'sunset') { 
    $font_import = "https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap"; $font_family = "'Poppins', sans-serif"; $btn_class = "bg-orange-500 hover:bg-orange-600 text-white rounded-xl shadow-xl"; $variant_active_class = "peer-checked:bg-orange-500 peer-checked:text-white peer-checked:border-orange-500"; 
}
elseif ($theme === 'pastel') { 
    $font_import = "https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap"; $font_family = "'Quicksand', sans-serif"; $bg_color = "bg-[#fdf2f8]"; $text_color = "text-[#831843]"; $text_muted = "text-[#f472b6]"; $card_bg = "bg-white"; $border_class = "border-pink-100"; $btn_class = "bg-gradient-to-r from-pink-400 to-rose-400 hover:from-pink-500 hover:to-rose-500 text-white rounded-full shadow-lg shadow-pink-200 font-bold"; $input_bg = "bg-pink-50"; $variant_active_class = "peer-checked:bg-pink-500 peer-checked:text-white peer-checked:border-pink-500"; 
}

// LUXURY LAYOUT
elseif ($theme === 'midnight') { 
    $layout_style = 'luxury';
    $font_import = "https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap"; $font_family = "'Inter', sans-serif"; $bg_color = "bg-[#0a0a0a]"; $text_color = "text-gray-100"; $text_muted = "text-gray-400"; $card_bg = "bg-[#171717]"; $border_class = "border-gray-800"; $btn_class = "bg-white hover:bg-gray-200 text-black rounded-sm shadow-lg font-bold tracking-widest uppercase text-xs"; $input_bg = "bg-[#262626]"; $variant_active_class = "peer-checked:bg-white peer-checked:text-black peer-checked:border-white"; 
} 
elseif ($theme === 'gold') { 
    $layout_style = 'luxury';
    $font_import = "https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;1,400&display=swap"; $font_family = "'Playfair Display', serif"; $bg_color = "bg-[#faf9f6]"; $text_color = "text-[#332b22]"; $text_muted = "text-[#8a7f72]"; $card_bg = "bg-white"; $border_class = "border-[#eaddb6]"; $btn_class = "bg-gradient-to-r from-[#bf953f] via-[#fcf6ba] to-[#b38728] hover:brightness-110 text-black rounded-sm uppercase tracking-widest font-black shadow-lg"; $input_bg = "bg-[#fffdf8]"; $variant_active_class = "peer-checked:bg-[#bf953f] peer-checked:text-black peer-checked:border-[#bf953f]"; 
}
elseif ($theme === 'vintage') { 
    $layout_style = 'luxury';
    $font_import = "https://fonts.googleapis.com/css2?family=PT+Serif:ital,wght@0,400;0,700;1,400&display=swap"; $font_family = "'PT Serif', serif"; $bg_color = "bg-[#f4ecd8]"; $text_color = "text-[#4a3b32]"; $text_muted = "text-[#8a7666]"; $card_bg = "bg-[#fffbf0]"; $border_class = "border-[#d4c5b4]"; $btn_class = "bg-[#5c4a3d] hover:bg-[#3d3128] text-[#f4ecd8] rounded-sm border border-[#4a3b32] shadow-sm uppercase tracking-widest font-bold text-xs"; $input_bg = "bg-[#fffbf0]"; $variant_active_class = "peer-checked:bg-[#5c4a3d] peer-checked:text-[#f4ecd8] peer-checked:border-[#4a3b32]"; 
}

// BRUTALIST LAYOUT
elseif ($theme === 'street') { 
    $layout_style = 'brutalist';
    $font_import = "https://fonts.googleapis.com/css2?family=Oswald:wght@400;700;900&display=swap"; $font_family = "'Oswald', sans-serif"; $bg_color = "bg-white"; $text_color = "text-black"; $text_muted = "text-gray-500"; $card_bg = "bg-white"; $border_class = "border-[3px] border-black"; $btn_class = "bg-black hover:bg-transparent hover:text-black text-white border-[3px] border-black rounded-none shadow-[4px_4px_0px_0px_rgba(0,0,0,1)] uppercase font-black tracking-widest transition-all hover:translate-x-1 hover:translate-y-1 hover:shadow-none"; $input_bg = "bg-white"; $variant_active_class = "peer-checked:bg-black peer-checked:text-white peer-checked:border-black peer-checked:shadow-[4px_4px_0px_0px_rgba(0,0,0,1)]"; 
}
elseif ($theme === 'cyber') { 
    $layout_style = 'brutalist';
    $font_import = "https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap"; $font_family = "'Share Tech Mono', monospace"; $bg_color = "bg-[#050505]"; $text_color = "text-[#00ff00]"; $text_muted = "text-[#008800]"; $card_bg = "bg-[#0a0a0a]"; $border_class = "border-2 border-[#00ff00]"; $btn_class = "bg-transparent hover:bg-[#00ff00] text-[#00ff00] hover:text-black border-2 border-[#00ff00] rounded-none shadow-[0_0_10px_rgba(0,255,0,0.3)] hover:shadow-[0_0_20px_rgba(0,255,0,0.8)] uppercase font-bold tracking-widest transition-all"; $input_bg = "bg-[#000000]"; $variant_active_class = "peer-checked:bg-[#00ff00] peer-checked:text-black peer-checked:border-[#00ff00] peer-checked:shadow-[0_0_10px_rgba(0,255,0,0.5)]"; 
}
elseif ($theme === 'neon') { 
    $layout_style = 'brutalist';
    $font_import = "https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;700&display=swap"; $font_family = "'Space Grotesk', sans-serif"; $bg_color = "bg-[#09090b]"; $text_color = "text-white"; $text_muted = "text-gray-400"; $card_bg = "bg-[#121214]"; $border_class = "border-2 border-fuchsia-600"; $btn_class = "bg-gradient-to-r from-fuchsia-600 to-cyan-500 hover:from-fuchsia-500 hover:to-cyan-400 text-white rounded-none shadow-[0_0_15px_rgba(192,38,211,0.5)] border-2 border-transparent hover:border-white uppercase font-black tracking-widest"; $input_bg = "bg-[#000000]"; $variant_active_class = "peer-checked:bg-fuchsia-600 peer-checked:text-white peer-checked:border-fuchsia-400 peer-checked:shadow-[0_0_15px_rgba(192,38,211,0.5)]"; 
}
?>
