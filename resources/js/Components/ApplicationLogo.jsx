export default function ApplicationLogo(props) {
  return (
    <img
      src="/brand/tamrose-logo.png"   // put your file here; fallback below
      onError={(e)=>{ e.currentTarget.src='/favicon.ico'; }}
      alt="Tamrose"
      className={`h-8 w-auto ${props.className ?? ''}`}
    />
  );
}
