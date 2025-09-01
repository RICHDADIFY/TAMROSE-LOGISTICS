// resources/js/Components/ContactPanel.jsx
import React from "react";

/* --- tiny inline icons (no dependencies) --- */
function Icon({ name, className = "h-4 w-4" }) {
  switch (name) {
    case "phone":
      return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
          <path d="M22 16.92v2a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.08 4.18 2 2 0 0 1 4.06 2h2a2 2 0 0 1 2 1.72c.12.9.32 1.77.59 2.61a2 2 0 0 1-.45 2.11L7.1 9.91a16 16 0 0 0 6 6l1.47-1.12a2 2 0 0 1 2.11-.45c.84.27 1.71.47 2.61.59A2 2 0 0 1 22 16.92Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
      );
    case "sms":
      return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
          <path d="M21 15a4 4 0 0 1-4 4H8l-4 4V7a4 4 0 0 1 4-4h9a4 4 0 0 1 4 4v8Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          <path d="M8 9h8M8 13h5" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
        </svg>
      );
    case "whatsapp":
      return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
          <path d="M20.5 11.5A8.5 8.5 0 0 1 8.35 20.3L4 21.5l1.2-4.3A8.5 8.5 0 1 1 20.5 11.5Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          <path d="M8.7 9.5c.2-.6.4-.7.7-.7.2 0 .5 0 .7.4.2.5.7 1.3.7 1.3.1.2.1.4-.1.6l-.4.5c-.1.1 0 .3 0 .4a4.5 4.5 0 0 0 2.1 2.1c.1.1.3.1.4 0l.5-.4c.2-.2.4-.2.6-.1 0 0 .8.5 1.3.7.4.2.4.5.4.7 0 .3-.1.5-.3.7l-.2.2c-.3.3-.7.4-1.2.3-1.3-.2-2.6-.9-3.7-1.9-1.1-1.1-1.7-2.4-1.9-3.7-.1-.5 0-.9.3-1.2l.2-.2Z" fill="currentColor"/>
        </svg>
      );
    case "mail":
      return (
        <svg viewBox="0 0 24 24" fill="none" className={className}>
          <path d="M4 6h16a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2Z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          <path d="m22 8-10 7L2 8" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
        </svg>
      );
    default:
      return null;
  }
}

function initialsFrom(name = "") {
  const parts = String(name).trim().split(/\s+/).slice(0, 2);
  return parts.map((p) => p[0]?.toUpperCase() || "").join("") || "LM";
}

function RoundIconButton({ href, title, color }) {
  if (!href) return null;
  const isWA = title === "WhatsApp";
  return (
    <a
      href={href}
      target={isWA ? "_blank" : undefined}
      rel={isWA ? "noreferrer" : undefined}
      className={`h-9 w-9 rounded-full grid place-items-center text-white ${color} transition`}
      title={title}
    >
      {title === "Call" && <Icon name="phone" />}
      {title === "SMS" && <Icon name="sms" />}
      {title === "WhatsApp" && <Icon name="whatsapp" />}
      {title === "Email" && <Icon name="mail" />}
    </a>
  );
}

function ContactCard({ c }) {
  const tel  = c.phone ? `tel:${c.phone}` : null;
  const sms  = c.phone ? `sms:${c.phone}` : null;
  const wa   = c.phone ? `https://wa.me/${String(c.phone).replace(/\D/g,"")}` : null;
  const mail = c.email ? `mailto:${c.email}` : null;

  const label =
    c.tag === "driver" ? "Assigned Driver" :
    c.tag === "manager" ? "Logistics Manager" :
    c.tag === "requester" ? "Requester" : "Contact";

  return (
    <div className="rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 bg-white dark:bg-slate-900 p-3 mb-2">
      {/* top: avatar + identity */}
      <div className="flex items-center gap-3">
        {c.avatar ? (
          <img
            src={c.avatar}
            alt=""
            className="h-11 w-11 rounded-full object-cover ring-2 ring-emerald-500/20 flex-shrink-0"
          />
        ) : (
          <div className="h-11 w-11 rounded-full bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-200 grid place-items-center font-bold ring-2 ring-emerald-500/10 flex-shrink-0">
            {initialsFrom(c.name)}
          </div>
        )}

        <div className="flex-1 min-w-0">
          <div className="font-semibold text-gray-900 dark:text-gray-100 truncate">{c.name}</div>
          <div className="text-xs text-gray-500 dark:text-gray-400">{label}</div>
          {c.email && (
            <div className="text-xs text-gray-500 dark:text-gray-400 truncate">{c.email}</div>
          )}
        </div>
      </div>

      {/* bottom: buttons under description */}
      {/* bottom row: buttons */}
        <div className="flex gap-3 mt-3 justify-center">
          <RoundIconButton href={tel}  title="Call"     color="bg-emerald-600 hover:bg-emerald-700" />
          <RoundIconButton href={sms}  title="SMS"      color="bg-amber-500 hover:bg-amber-600" />
          <RoundIconButton href={wa}   title="WhatsApp" color="bg-green-600 hover:bg-green-700" />
          <RoundIconButton href={mail} title="Email"    color="bg-gray-500 hover:bg-gray-600" />
        </div>

    </div>
  );
}

export default function ContactPanel({ contacts = [] }) {
  if (!contacts.length) return null;
  return (
    <div className="mt-2">
      {contacts.map((c, idx) => <ContactCard key={idx} c={c} />)}
    </div>
  );
}
