export function getGreeting(): string {
  const hour = new Date().getHours();
  
  if (hour >= 0 && hour < 12) {
    return 'Buenos días';
  } else if (hour >= 12 && hour < 20) {
    return 'Buenas tardes';
  } else {
    return 'Buenas noches';
  }
}
