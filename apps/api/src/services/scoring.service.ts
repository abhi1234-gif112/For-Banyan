export function scoreMention(text:string){const lowered=text.toLowerCase();const negative=['scam','fraud','riot','arrest','corruption','violence'];const hits=negative.filter(w=>lowered.includes(w)).length;return Math.min(100,hits*25);}
export function detectSeverity(score:number){return score>=80?'CRITICAL':score>=60?'HIGH':score>=30?'MEDIUM':'LOW';}
