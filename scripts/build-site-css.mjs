import {readFileSync,writeFileSync,mkdirSync} from 'node:fs';
import {transform} from 'esbuild';
mkdirSync('public/site-assets',{recursive:true});
for(const theme of ['classic','modern']) {
 const paths=['public/fonts/ibm-plex-arabic.css','public/helal.css',...(theme==='modern'?['public/helal-modern.css']:[]),'public/helal-pages.css','public/helal-refinement.css'];
 const source=paths.map(p=>readFileSync(p,'utf8').replaceAll('url(./','url(../fonts/')).join('\n');
 const {code}=await transform(source,{loader:'css',minify:true});
 writeFileSync(`public/site-assets/${theme}.css`,code);
}
